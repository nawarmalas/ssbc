# Dynamic Form Builder & Submissions Manager — Design Spec
**Date:** 2026-05-18  
**Project:** SSBC Website (Laravel 11)  
**Scope:** Join Us membership form — fully dynamic, admin-managed

---

## 1. Overview

Replace the hardcoded 4-step Blade form at `/join` with a fully dynamic form system. Admins manage sections and fields from a visual builder at `/admin/forms/join-us` without touching code. Submissions are stored in relational tables and managed from `/admin/submissions`.

**Technology choices:**
- Admin builder UI: Alpine.js + SortableJS + Fetch API (matches existing stack)
- PDF export: `barryvdh/laravel-dompdf`
- Excel export: `maatwebsite/laravel-excel`
- Google Sheets: POST to `GOOGLE_SCRIPT_URL` from `.env`
- Email: Laravel Mail (queued jobs)

**Preserved:** The existing `membership_applications` table and admin routes at `/admin/membership` are left intact. The new system runs independently.

---

## 2. Database Schema

### 2.1 `form_sections`
```sql
id               bigint unsigned PK auto_increment
form_id          varchar(64)        -- e.g. 'join-us'
title_en         varchar(255)
title_ar         varchar(255)
is_repeatable    tinyint(1) default 0
max_repeats      tinyint unsigned default 5
order_index      smallint unsigned default 0
created_at       timestamp
updated_at       timestamp

INDEX (form_id, order_index)
```

### 2.2 `form_fields`
```sql
id               bigint unsigned PK auto_increment
section_id       bigint unsigned FK → form_sections.id (cascade delete)
label_en         varchar(255)
label_ar         varchar(255)
placeholder_en   varchar(255) nullable
placeholder_ar   varchar(255) nullable
field_type       enum('text','textarea','email','tel','number','date',
                      'select','radio','checkbox_group','file','url','declaration')
is_required      tinyint(1) default 1
is_active        tinyint(1) default 1
order_index      smallint unsigned default 0
options          json nullable   -- [{label_en, label_ar, value}, ...]
validation_rules json nullable   -- {min, max, pattern, ...}
file_config      json nullable   -- {accepted_types: [], max_size_mb: int}

INDEX (section_id, order_index)
```

### 2.3 `form_submissions`
```sql
id               bigint unsigned PK auto_increment
form_id          varchar(64)
display_name     varchar(255) nullable  -- denormalized applicant name for admin list
ip_address       varchar(45) nullable
status           enum('pending','under_review','approved','rejected') default 'pending'
admin_notes      text nullable
submitted_at     timestamp
created_at       timestamp
updated_at       timestamp

INDEX (form_id, submitted_at)
```

### 2.4 `form_answers`
```sql
id               bigint unsigned PK auto_increment
submission_id    bigint unsigned FK → form_submissions.id (cascade delete)
field_id         bigint unsigned    -- no FK; field may be deleted later
repeat_index     tinyint unsigned default 0  -- 0=first repeat, 1=second, etc.
answer_value     text nullable
created_at       timestamp

INDEX (submission_id, field_id, repeat_index)
```

### 2.5 `form_uploads`
```sql
id               bigint unsigned PK auto_increment
submission_id    bigint unsigned FK → form_submissions.id (cascade delete)
field_id         bigint unsigned
repeat_index     tinyint unsigned default 0
file_path        varchar(500)
file_name        varchar(255)
file_size        int unsigned
created_at       timestamp

INDEX (submission_id)
```

---

## 3. Supported Field Types

| Type | Renders as | Notes |
|------|-----------|-------|
| `text` | `<input type="text">` | |
| `textarea` | `<textarea>` | |
| `email` | `<input type="email">` | Laravel `email` validation |
| `tel` | `<input type="tel">` | |
| `number` | `<input type="number">` | Uses `validation_rules.min/max` |
| `date` | `<input type="date">` | |
| `select` | `<select>` | Options from `options` JSON |
| `radio` | Radio button group | Options from `options` JSON |
| `checkbox_group` | Checkbox group | Answer stored as JSON array string |
| `file` | Drag-and-drop zone | Config from `file_config` JSON |
| `url` | `<input type="url">` | |
| `declaration` | Single checkbox | Full text from `label_en`/`label_ar` |

---

## 4. Admin Form Builder

### 4.1 Routes
All under `admin` prefix, behind `auth` middleware:

```
GET    /admin/forms/join-us                     FormBuilderController@index
POST   /admin/forms/join-us/sections            FormBuilderController@storeSection
PUT    /admin/forms/join-us/sections/{section}  FormBuilderController@updateSection
DELETE /admin/forms/join-us/sections/{section}  FormBuilderController@destroySection
POST   /admin/forms/join-us/sections/reorder    FormBuilderController@reorderSections

POST   /admin/forms/join-us/fields              FormBuilderController@storeField
PUT    /admin/forms/join-us/fields/{field}      FormBuilderController@updateField
DELETE /admin/forms/join-us/fields/{field}      FormBuilderController@destroyField
POST   /admin/forms/join-us/fields/reorder      FormBuilderController@reorderFields

GET    /admin/forms/join-us/preview             FormBuilderController@preview
```

All mutating routes return `{success: true, data: {...}}` JSON. The `index` route returns a Blade view.

### 4.2 Controller: `App\Http\Controllers\Admin\FormBuilderController`
Thin — delegates to `FormService`. Key methods:
- `index()` → loads all sections+fields for `join-us`, passes as JSON variable to view
- `storeSection()` / `updateSection()` → validates `title_en`, `title_ar`, `is_repeatable`, `max_repeats`
- `destroySection()` → checks for fields; returns `{has_fields: true, count: N}` if present, deletes only when confirmed via `?force=1`
- `reorderSections()` → accepts `[{id, order_index}]` array, bulk updates
- `storeField()` / `updateField()` → validates per field_type, saves JSON columns
- `destroyField()` → soft-deletes answers? No — hard delete field; answers remain (field_id is not FK-constrained)
- `reorderFields()` → same pattern as sections
- `preview()` → renders `join.create` with `$preview = true` flag. The view checks this flag to: hide the `<form>` submit action (renders as `action="#"`), disable the Submit button, and show a banner "Preview Mode — this form cannot be submitted from here."

### 4.3 Alpine Component: `formBuilder()`
Defined in an inline `<script>` on the builder page:

```js
{
  sections: [],          // full tree loaded from PHP JSON
  editingField: null,    // field object in modal (null = modal closed)
  editingSection: null,  // section being renamed inline
  saving: false,
  modalOpen: false,

  // methods
  openFieldModal(field, sectionId),
  saveField(),
  deleteField(fieldId, sectionId),
  addSection(),
  saveSection(section),
  deleteSection(sectionId, force),
  initSortable(),        // init SortableJS on mount, reinit after mutations
  onSectionReorder(evt),
  onFieldReorder(evt, sectionId),
}
```

SortableJS loaded via CDN. Initialized with `animation: 150`, `handle: '.drag-handle'`. On `onEnd`, fires reorder fetch.

### 4.4 Field Modal Sub-panels
Shown/hidden based on `editingField.field_type`:

- **Options builder** (`select`, `radio`, `checkbox_group`): dynamic list of rows `{label_en, label_ar, value}`. Add row / delete row. Value auto-slugified from `label_en` if empty.
- **File config** (`file`): checkboxes for accepted types (pdf, jpg, png, doc, docx) + number input for max MB (default 5).
- **Declaration text** (`declaration`): `label_en` and `label_ar` textareas hold the full declaration text (reuses label fields, no separate column needed).
- **Validation rules** (shown for `text`, `textarea`, `number`, `url`): optional min/max length or value.

### 4.5 Section Deletion Guard
If `destroySection()` returns `{has_fields: true}`, Alpine shows an inline warning:
> "This section has N fields. All fields and any saved answers for those fields will be permanently deleted."
Confirm button re-sends request with `?force=1`.

---

## 5. Public Join Us Form

### 5.1 FormService: `App\Services\FormService`
```php
static function getActiveForm(string $formId): Collection
// Returns sections (ordered) with their active fields (ordered)
// Eager-loaded, cached with Cache::remember for 5 minutes
// Cache key: "form:{formId}:sections"
// Cache invalidated (Cache::forget) in every FormBuilderController mutation method
```

### 5.2 Controller Changes
`JoinController::create()` replaced:
```php
public function create(string $locale)
{
    $form = FormService::getActiveForm('join-us');
    return view('join.create', compact('form'));
}
```

`JoinController::store()` replaced with dynamic processing (see §5.4).

### 5.3 Alpine Component: `dynamicForm(config)`
```js
{
  sections: config.sections,   // injected from PHP as JSON
  step: 0,                     // current section index
  totalSteps: N,
  answers: {},                 // {field_id: value} or {field_id+'_'+repeat: value}
  repeats: {},                 // {section_id: count} — number of repeats shown
  files: {},                   // {field_id+'_'+repeat: File}
  errors: {},

  // computed
  currentSection(),
  progressPct(),
  isRepeatableSection(),
  repeatCount(sectionId),

  // methods
  next(),        // validates current step required fields, advances
  back(),
  addRepeat(sectionId),       // increments repeats[sectionId] up to max_repeats
  removeRepeat(sectionId),
  validateStep(),             // returns bool, populates errors{}
  handleFileSelect(fieldId, repeatIndex, file),  // validates type+size
  onSubmit(e),
}
```

### 5.4 Submission Flow (`store()`)
1. **Rate limit:** `throttle:5,1` middleware on route
2. **CSRF:** standard Laravel `VerifyCsrfToken`
3. **Server validation:**
   - Re-fetch active form fields
   - Form submits `_repeats[section_id] = N` hidden fields so server knows repeat counts per section
   - For each required field (per section, per repeat index up to submitted count), assert answer present
   - For each file field, validate `mimes` and `max` using Laravel validation rules
4. **Persist:**
   ```php
   $submission = FormSubmission::create([
       'display_name' => $request->input('answers.'.$nameFieldId),
       ...
   ]);
   // Loop text answers → FormAnswer::insert([...])
   // Loop files → store to storage/app/public/submissions/{uuid}/ → FormUpload::insert([...])
   ```
5. **Notifications (queued):**
   - `SendAdminNotification::dispatch($submission)` → HTML table email to `info@sysabc.org`
   - `SendApplicantConfirmation::dispatch($submission)` → confirmation email to applicant's email answer
6. **Google Sheets (fire-and-forget):**
   ```php
   try {
       Http::post(config('services.google_script_url'), $payload);
   } catch (\Throwable) { /* silent */ }
   ```
7. **Redirect** → `join.thanks`

### 5.5 File Uploads
- Stored at `storage/app/public/submissions/{uuid}/{fieldId}_{repeat}_{filename}`
- Validated server-side with `mimes` and `max` rules built from `file_config` JSON
- Client-side drag-and-drop zone: shows filename after selection, red error if wrong type/size
- No executable types allowed (`mimes` rule naturally excludes them)

---

## 6. Submissions Management

### 6.1 Routes
```
GET    /admin/submissions                          SubmissionController@index
GET    /admin/submissions/{submission}             SubmissionController@show
PATCH  /admin/submissions/{submission}             SubmissionController@update
DELETE /admin/submissions/{submission}             SubmissionController@destroy
GET    /admin/submissions/{submission}/pdf         SubmissionController@pdf
GET    /admin/submissions/export                   SubmissionController@export
```

### 6.2 Index Page
- Follows existing admin table pattern (matches `admin/membership/index.blade.php`)
- Columns: Date, Applicant Name, Company Name, Status, Actions
- Name shown from `form_submissions.display_name` (denormalized at submit time — no join needed)
- Company name: resolved via `FormAnswer` where `field_id` matches the Company Name field in section 2, `repeat_index = 0`
- Date filter: `from` + `to` GET params, plain form submit (no JS)
- Status badges: `ssbc-status-pending`, `ssbc-status-under_review`, `ssbc-status-approved`, `ssbc-status-rejected`

### 6.3 Detail Page
- Answers grouped by section, each section a card
- Repeatable sections show as "Company 1", "Company 2", etc.
- File answers rendered as `<a href="{{ Storage::url($upload->file_path) }}" download>filename</a>`
- Inline status form (select + save) — PATCH to update route
- Admin notes textarea — same PATCH
- Delete button with JS `confirm()` guard

### 6.4 PDF Export
- Route: `GET /admin/submissions/{submission}/pdf`
- Renders `resources/views/admin/submissions/pdf.blade.php` via DomPDF
- Layout: SSBC logo header, submission date, answers in two-column table grouped by section
- Response: `Content-Disposition: attachment; filename="ssbc-submission-{id}.pdf"`

### 6.5 Excel Export
- Route: `GET /admin/submissions/export?from=&to=`
- `App\Exports\SubmissionsExport` implements `FromQuery` + `WithHeadings` + `ShouldQueue`
- Headers: dynamically built from all active `form_fields` labels (EN). Repeatable fields get suffixed: "Company Name (1)", "Company Name (2)", etc. (up to `max_repeats`)
- Each row: one submission, answers flattened
- Response: `.xlsx` download

---

## 7. Security Summary

| Concern | Solution |
|---------|----------|
| Admin auth | Existing `auth` middleware on all admin routes |
| CSRF | `@csrf` on Blade forms; `X-CSRF-TOKEN` header on Fetch calls |
| File type safety | Laravel `mimes:` validation; no executable extensions possible |
| File storage | `storage/app/public/` — not directly web-accessible, served via signed URLs |
| XSS | Blade `{{ }}` auto-escaping on all output |
| Input sanitization | `strip_tags()` on all text answers before DB insert |
| Rate limiting | `throttle:5,1` on `join.store` |
| SQL injection | Eloquent ORM throughout — no raw queries |

---

## 8. Migration & Seeder

### 8.1 Migration
Single file: `2026_05_18_000000_create_form_tables.php`  
Creates all 5 tables in dependency order with indexes and foreign keys.

### 8.2 FormSeeder
Pre-populates `join-us` form. Run via `DatabaseSeeder`.

**Section 1 — Personal Information (8 fields):**

| Field | Type | Required | AR Label |
|-------|------|----------|----------|
| Full Name in Arabic (as in Passport) | text | yes | الاسم الكامل بالعربية (كما في جواز السفر) |
| Full Name in English (as in Passport) | text | yes | الاسم الكامل بالإنجليزية (كما في جواز السفر) |
| Date of Birth | date | yes | تاريخ الميلاد |
| Current Position | text | yes | المسمى الوظيفي الحالي |
| Mobile Number with Country Code | tel | yes | رقم الجوال مع رمز الدولة |
| Email Address | email | yes | البريد الإلكتروني |
| Home Address | textarea | no | العنوان السكني |
| LinkedIn Profile Link | url | no | رابط الملف الشخصي على لينكد إن |

**Section 2 — Company Information (`is_repeatable=true`, `max_repeats=5`, 8 fields):**

| Field | Type | Required | Options |
|-------|------|----------|---------|
| Company Name | text | yes | — |
| Company Establishment Year | number | yes | validation: min=1900, max=current year |
| Company Size | radio | yes | 1–10 / 11–50 / 51–200 / 200+ (EN+AR) |
| Business Address | textarea | yes | — |
| Company Website | url | no | — |
| Current Operations Country | radio | no | Syria / KSA / Both / Other (EN+AR) |
| Sectors of Operation | checkbox_group | yes | 12 options (EN+AR — see below) |
_(The "Do you have an additional company?" radio from the original form is **not seeded** — the repeatable section's "Add another company" button replaces it entirely.)_

12 sector options (EN / AR):
Agriculture and Livestock / الزراعة والثروة الحيوانية,
Oil and Mineral Resources / النفط والموارد المعدنية,
Electricity and Water / الكهرباء والمياه,
Health and Pharmaceuticals / الصحة والدواء,
Real Estate Development and Construction / التطوير العقاري والإنشاء,
Education and Training / التعليم والتدريب,
Tourism / السياحة,
Drama and Media / الدراما والإعلام,
Development Work / العمل التنموي,
Transport and Logistics / النقل واللوجستيات,
Telecommunications / IT / Business Incubators / الاتصالات وتقنية المعلومات وحاضنات الأعمال,
Legal Consulting and Services / الاستشارات والخدمات القانونية

**Section 3 — Required Documents (3 fields):**

| Field | Type | Required | Config |
|-------|------|----------|--------|
| Copy of ID or Passport | file | yes | accepted: jpg,jpeg,png,pdf; max: 5MB |
| Commercial Registry or Trade License | file | yes | accepted: jpg,jpeg,png,pdf,doc,docx; max: 5MB |
| Company Profile | file | no | accepted: pdf,doc,docx; max: 5MB |

**Section 4 — Declaration (1 field):**

| Field | Type | Required |
|-------|------|----------|
| Declaration checkbox | declaration | yes |

EN text: "I declare that all provided information and attached documents are true and accurate, and I consent to sharing them with the SSBC committees for evaluation."  
AR text: أُقرّ بأن جميع المعلومات والمستندات المقدمة صحيحة ودقيقة، وأوافق على مشاركتها مع لجان المجلس للتقييم.

---

## 9. New Packages Required

```bash
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
```

SortableJS loaded via CDN in admin layout (no npm required).

---

## 10. Files to Create / Modify

### New files
```
app/Http/Controllers/Admin/FormBuilderController.php
app/Http/Controllers/Admin/SubmissionController.php
app/Services/FormService.php
app/Models/FormSection.php
app/Models/FormField.php
app/Models/FormSubmission.php
app/Models/FormAnswer.php
app/Models/FormUpload.php
app/Exports/SubmissionsExport.php
app/Mail/AdminSubmissionNotification.php
app/Mail/ApplicantConfirmation.php
database/migrations/2026_05_18_000000_create_form_tables.php
database/seeders/FormSeeder.php
resources/views/admin/form-builder/index.blade.php
resources/views/admin/submissions/index.blade.php
resources/views/admin/submissions/show.blade.php
resources/views/admin/submissions/pdf.blade.php
resources/views/mail/admin-notification.blade.php
resources/views/mail/applicant-confirmation.blade.php
```

### Modified files
```
routes/web.php                              — add new admin + public routes
app/Http/Controllers/JoinController.php    — rewrite create() and store()
resources/views/join/create.blade.php      — rewrite as dynamic renderer
database/seeders/DatabaseSeeder.php        — call FormSeeder
.env.example                               — add GOOGLE_SCRIPT_URL=
config/services.php                        — add google_script_url entry
```

### Preserved (no changes)
```
app/Http/Controllers/Admin/MembershipController.php
app/Models/MembershipApplication.php
database/migrations/2026_01_01_000040_create_membership_applications_table.php
resources/views/admin/membership/*
```
