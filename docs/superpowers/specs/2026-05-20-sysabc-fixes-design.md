# SYSABC.ORG — 5-Fix Design Spec
**Date:** 2026-05-20  
**Reviewed by:** Claude Sonnet 4.6 + Claude Opus (architecture review)

---

## Canonical tree

All edits target the top-level `app/` and `resources/` tree. The `ssbc_app/` subtree is a stale copy — do not touch it.

---

## Database changes (new columns, new migrations)

Five new columns across two tables, all added in dedicated migrations:

| Table | Column | Type | Purpose |
|-------|--------|------|---------|
| `form_fields` | `code` | `string(64) nullable unique(section_id, code)` | Stable identifier; used by SectorObserver and conditional_logic references |
| `form_fields` | `conditional_logic` | `json nullable` | Conditional display rules (replaces any use of `validation_rules` for UI logic) |
| `form_fields` | `is_system_managed` | `boolean default false` | Guards sector-managed field from FormBuilder UI edits/delete |
| `sectors` | `slug` | `string(80) nullable unique` | Frozen at create; used as option `value` in form_fields.options |
| `sectors` | `deleted_at` | `timestamp nullable` | Soft-delete; enables `withTrashed()` resolution in `formatAnswer()` |

All five added to the respective model's `$fillable` and `casts()`.

---

## Fix 1 — Strategic Pillars: switch to dynamic sectors

**File:** `resources/views/pages/home.blade.php` (section 4, lines 115–122)

Replace:
```blade
@foreach($site->homeList($locale, 'pillars.items', (array) __('home.pillars.items')) as $item)
    <div class="ssbc-pillar-card">
        <h3 ...>{{ $item['title'] ?? '' }}</h3>
        <p ...>{{ $item['desc'] ?? '' }}</p>
    </div>
@endforeach
```

With:
```blade
@foreach($sectors as $sector)
    <div class="ssbc-pillar-card">
        <h3 class="text-lg font-display font-semibold text-ssbc-green mb-2">{{ $sector->name() }}</h3>
        <p class="text-sm text-ssbc-dark/75 leading-relaxed">{{ $sector->description() }}</p>
    </div>
@endforeach
```

`$sectors` is already passed from `HomeController::index()` — no controller change needed.

**Grid:** Keep `lg:grid-cols-3`. 15 items = 5 rows. The section heading/body copy currently uses `home.pillars.*` translation keys — keep as-is (the admin can update them via Website Customization if desired).

**Admin management:** The existing `/admin/sectors` CRUD (add, edit bilingual name+description, reorder, show/hide, delete) is the management interface for Strategic Pillars. No new admin UI needed.

---

## Fix 2 — Remove Economic Sectors section

**File:** `resources/views/pages/home.blade.php` line 127

Remove:
```blade
{{-- 4b. Sectors --}}
@include('pages.partials.sectors')
```

Delete file: `resources/views/pages/partials/sectors.blade.php`

`HomeController` keeps the `$sectors` variable — it's needed for Fix 1.

---

## Fix 3 — Sectors: single source of truth

### 3a. Frozen slug on Sector model

Add `booted()` hook to `app/Models/Sector.php`:
```php
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

// In class body:
use SoftDeletes;

protected static function booted(): void
{
    static::creating(function (Sector $s) {
        if (! $s->slug) {
            $base = Str::slug($s->name_en ?: $s->name_ar);
            $slug = $base; $i = 2;
            while (static::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $s->slug = $slug;
        }
    });
}
```

Slug is generated at create time only — never regenerated when `name_en` is updated. Uniqueness ensured by loop + DB unique index.

### 3b. SectorObserver

New file: `app/Observers/SectorObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\FormField;
use App\Models\Sector;
use App\Services\FormService;
use Illuminate\Support\Facades\DB;

class SectorObserver
{
    public function saved(Sector $sector): void    { $this->sync(); }
    public function deleted(Sector $sector): void  { $this->sync(); }
    public function restored(Sector $sector): void { $this->sync(); }

    private function sync(): void
    {
        DB::afterCommit(function () {
            $field = FormField::where('code', 'sectors_of_operation')->first();
            if (! $field) return;

            $options = Sector::query()
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($s) => [
                    'value'    => $s->slug,
                    'label_en' => $s->name_en,
                    'label_ar' => $s->name_ar,
                ])->all();

            $field->forceFill(['options' => $options])->saveQuietly();
            FormService::invalidateCache($field->section->form_id ?? 'join-us');
        });
    }
}
```

Register in `app/Providers/AppServiceProvider.php::boot()`:
```php
\App\Models\Sector::observe(\App\Observers\SectorObserver::class);
```

`saveQuietly()` prevents recursive model events. `DB::afterCommit` is a no-op outside a transaction (runs immediately), safe for both controllers and seeders.

### 3c. One-time migration: seed sectors field with code and sync options

A dedicated migration:
1. Sets `code = 'sectors_of_operation'` and `is_system_managed = true` on the "Sectors of Operation" form field
2. Rebuilds its `options` JSON from `Sector::active()->orderBy('sort_order')->get()` using `$s->slug` as value

### 3d. formatAnswer() fallback for deleted sectors

In `FormField::formatAnswer()`, inside the `$optionLabel` closure, after the `foreach` loop fails to find a match, add:
```php
// Fall back to Sector model (including soft-deleted) for system-managed fields
if ($this->code === 'sectors_of_operation') {
    $sector = \App\Models\Sector::withTrashed()->where('slug', $value)->first();
    if ($sector) {
        return $locale === 'ar' ? ($sector->name_ar ?: $sector->name_en) : ($sector->name_en ?: $sector->name_ar);
    }
}
return $value;
```

### 3e. FormBuilderController guard

In `validateField()`, after validation, strip protected columns for system-managed fields:
```php
$existing = isset($field) ? $field : null; // passed as parameter
if ($existing && $existing->is_system_managed) {
    unset($data['options'], $data['field_type']);
}
```

In `destroyField()`, block deletion:
```php
if ($field->is_system_managed) {
    return response()->json(['error' => 'System-managed fields cannot be deleted.'], 422);
}
```

The form-builder admin Blade view should visually indicate `is_system_managed` fields (badge + disabled options editor + disabled field-type selector + no delete button). Labels, placeholders, order, and `is_active` remain editable.

---

## Fix 4 — Membership Form: "Other" country conditional input

### 4a. New conditional_logic schema

The `conditional_logic` JSON column on `form_fields` uses this structure:
```json
{
  "operator": "AND",
  "conditions": [
    { "field_code": "current_operations_country", "op": "equals", "value": "other" }
  ]
}
```

Supported `op` values: `equals`, `not_equals`, `in`, `not_in`, `contains` (for checkbox_group JSON arrays).

### 4b. New form field in DB (migration)

Add to Company Information section, order_index immediately after the country field:
```
label_en:          'Country Name (if Other)'
label_ar:          'اسم البلد (إذا اخترت أخرى)'
field_type:        'text'
is_required:       false
code:              'country_other_specify'
conditional_logic: {"operator":"AND","conditions":[{"field_code":"current_operations_country","op":"equals","value":"other"}]}
```

Also set `code = 'current_operations_country'` on the existing country radio field in this migration.

### 4c. FormSubmissionService: fieldIsVisible() helper

New private method used by both `rulesFor()` and `storeAnswers()`:

```php
private function fieldIsVisible(FormField $field, array $answers, int $repeat, Collection $form): bool
{
    $logic = $field->conditional_logic ?? null;
    if (! $logic) return true;

    $codeToId = $form->flatMap->fields->mapWithKeys(fn($f) => [$f->code => $f->id])->all();
    $results  = [];

    foreach ($logic['conditions'] as $c) {
        $targetId = $codeToId[$c['field_code']] ?? null;
        $val      = $targetId ? ($answers[$targetId][$repeat] ?? $answers[$targetId][0] ?? null) : null;

        $results[] = match ($c['op']) {
            'equals'     => $val === $c['value'],
            'not_equals' => $val !== $c['value'],
            'in'         => in_array($val, (array) $c['value'], true),
            'not_in'     => ! in_array($val, (array) $c['value'], true),
            'contains'   => is_array($val)
                              ? in_array($c['value'], $val, true)
                              : (is_string($val) && str_contains($val, (string) $c['value'])),
            default      => true,
        };
    }

    return ($logic['operator'] ?? 'AND') === 'OR'
        ? in_array(true, $results, true)
        : ! in_array(false, $results, true);
}
```

### 4d. rulesFor() change

Inside the per-field repeat loop, before building `$fieldRules`, check visibility:
```php
if (! $this->fieldIsVisible($field, $request->input('answers', []), $repeat, $form)) {
    $rules["answers.{$field->id}.{$repeat}"] = ['nullable'];
    continue;
}
```

### 4e. storeAnswers() change

Skip storing answers for hidden conditional fields:
```php
if (! $this->fieldIsVisible($field, $answers, (int) $repeatIndex, $form)) continue;
```

`$form` is loaded once at the top of `storeAnswers()` via `FormService::getActiveForm($submission->form_id)`.

### 4f. Fix checkbox_group required validation (latent bug)

In `rulesFor()`, for `checkbox_group` fields:
```php
$isCheckbox = $field->field_type === 'checkbox_group';
if ($isCheckbox) {
    $fieldRules = $field->is_required && $repeat === 0
        ? ['required', 'array', 'min:1']
        : ['nullable', 'array'];
} else {
    $fieldRules = [$field->is_required && $repeat === 0 ? 'required' : 'nullable'];
    // ... existing type branches ...
}
```

The Alpine.js frontend currently submits `checkbox_group` values as a JSON string via a hidden input. Before the required array validation works, the form must submit checkbox answers as a true PHP array (multiple `answers[id][0][]` inputs), OR the hidden input approach is replaced with individual named checkboxes. The hidden `JSON.stringify` approach in `join/create.blade.php` line 259–261 must change to emit standard array inputs so Laravel receives `array`, not a JSON string.

### 4g. Frontend (join/create.blade.php)

**Checkbox group submissions:** Replace the hidden JSON-serialized input with proper array inputs:
```blade
{{-- Remove the hidden JSON input (lines 259-261) --}}
{{-- Each checkbox now submits directly as answers[id][ri][] --}}
<input type="checkbox"
       :name="'answers[' + field.id + '][' + (ri-1) + '][]'"
       :value="opt.value"
       @change="toggleCheckbox(field.id, ri-1, opt.value)"
       ...>
```

**Conditional visibility:** After each field's template block, add an `x-show` wrapper controlled by `fieldIsVisible(field, ri-1)`. Add `fieldIsVisible(field, ri)` to the Alpine.js `dynamicForm()` component data:

```js
fieldIsVisible(field, ri) {
    const logic = field.conditional_logic;
    if (!logic) return true;
    const results = logic.conditions.map(c => {
        const val = this.answers[c.field_code_id + '_' + ri]
                    ?? this.answers[c.field_code_id + '_0'];
        switch (c.op) {
            case 'equals':     return val === c.value;
            case 'not_equals': return val !== c.value;
            case 'contains':   return Array.isArray(this.checkboxAnswers[c.field_code_id + '_' + ri])
                                    ? this.checkboxAnswers[c.field_code_id + '_' + ri].includes(c.value)
                                    : false;
            default:           return true;
        }
    });
    return logic.operator === 'OR' ? results.some(Boolean) : results.every(Boolean);
},
```

Note: the Alpine data needs `field_code_id` to be the resolved field ID (passed from PHP using the form's field list). A clean way: pass a `fieldCodeMap` object (`{code: id}`) alongside the form JSON.

**Conditional field required:** When `fieldIsVisible` returns true and the field `is_required`, the `:required` attribute on the input must evaluate true. When it returns false, the input is hidden and not submitted.

**Step validation:** The existing `validateStep()` in Alpine must skip fields where `fieldIsVisible === false`, and must check that required `checkbox_group` fields have at least one selection.

---

## Fix 5 — New Section 3: Interests and Cooperation

### Migration steps (single migration file, wrapped in DB::transaction):

1. Increment `order_index` for all `join-us` sections with `order_index >= 2`, using descending order to avoid unique collisions:
   ```php
   FormSection::where('form_id', 'join-us')
       ->where('order_index', '>=', 2)
       ->orderBy('order_index', 'desc')
       ->each(fn($s) => $s->increment('order_index'));
   ```

2. Insert the new section at `order_index = 2`:
   ```
   title_en: 'Section 3: Interests and Cooperation'
   title_ar: 'القسم الثالث: الاهتمامات وتوجهات التعاون'
   is_repeatable: false
   order_index: 2
   ```

3. Insert 4 FormFields into the new section:

**Q1 — Professional Profile** (checkbox_group, required)
```
label_en:  'What is your professional profile or nature of commercial interest?'
label_ar:  'ما هي صفتك المهنية أو طبيعة اهتمامك التجاري؟'
field_type: checkbox_group
is_required: true
code: professional_profile
options:
  - {value: investor,           label_en: Investor,                    label_ar: مستثمر}
  - {value: business_owner,     label_en: Business Owner,              label_ar: صاحب أعمال}
  - {value: strategic_partner,  label_en: Strategic Partner,           label_ar: شريك استراتيجي}
  - {value: service_provider,   label_en: Service Provider,            label_ar: مزود خدمات}
  - {value: consultant,         label_en: Consultant or Expert,        label_ar: استشاري أو خبير}
  - {value: other,              label_en: Other (Please specify),      label_ar: أخرى (يرجى التحديد)}
```

**Q1a — Other (specify)** (text, conditional)
```
label_en:  'Please specify (Other)'
label_ar:  'يرجى التحديد (أخرى)'
field_type: text
is_required: false
code: professional_profile_other
conditional_logic: {"operator":"AND","conditions":[{"field_code":"professional_profile","op":"contains","value":"other"}]}
```

**Q2 — Target Market** (checkbox_group, required)
```
label_en:  'What is the target market for your operations or investments?'
label_ar:  'ما هو السوق المستهدف لعملياتكم أو استثماراتكم؟'
field_type: checkbox_group
is_required: true
code: target_market
options:
  - {value: syrian_market,    label_en: Syrian Market,             label_ar: السوق السورية}
  - {value: saudi_market,     label_en: Saudi Market,              label_ar: السوق السعودية}
  - {value: both_markets,     label_en: Both Markets,              label_ar: كلا السوقين}
  - {value: other_regional,   label_en: Other Regional Markets,    label_ar: أسواق إقليمية أخرى}
```

**Q3 — Type of Cooperation** (checkbox_group, required)
```
label_en:  'What kind of cooperation are you looking for by joining the council?'
label_ar:  'ما نوع التعاون الذي تبحث عنه من خلال انضمامكم للمجلس؟'
field_type: checkbox_group
is_required: true
code: cooperation_type
options:
  - {value: investment_opps,    label_en: Exploring Investment Opportunities,            label_ar: البحث عن فرص استثمارية}
  - {value: joint_ventures,     label_en: Building Joint Ventures,                       label_ar: بناء مشاريع مشتركة}
  - {value: market_entry,       label_en: Market Entry Support and Facilitation,         label_ar: دعم وتسهيلات دخول السوق}
  - {value: strategic_partners, label_en: Establishing Strategic Partnerships,           label_ar: عقد شراكات استراتيجية}
  - {value: distribution,       label_en: Building Distribution Partnerships and Agencies, label_ar: بناء شراكات توزيع ووكالات}
  - {value: legal_support,      label_en: Legal and Regulatory Support,                  label_ar: الحصول على دعم قانوني وتنظيمي}
  - {value: matchmaking,        label_en: Business Matchmaking and Networking,           label_ar: توفيق الأعمال والتشبيك}
  - {value: export_import,      label_en: Export and Import Opportunities,               label_ar: البحث عن فرص تصدير واستيراد}
  - {value: financing,          label_en: Seeking Financing Opportunities,               label_ar: البحث عن فرص تمويلية}
```

4. Call `FormService::invalidateCache('join-us')` at end of migration (after transaction commits — not inside `DB::afterCommit` since this is a migration).

**Migration `down()`:** Delete the new section (and its fields cascade) by `title_en = 'Section 3: Interests and Cooperation'`, then decrement `order_index` for sections `>= 3`.

---

## Admin dashboard — no extra work needed

`SubmissionController::show()` loads `FormSection::with('allFields')->orderBy('order_index')` — new sections and fields appear automatically in the submission view and PDF export. The new Section 3 Q1/Q2/Q3 answers display via the existing `formatAnswer()` (checkbox_group decodes JSON array and maps values to labels from stored options).

---

## FormSeeder — no changes needed

The `FormSeeder` has an early-return guard (`if (FormSection::where('form_id', 'join-us')->exists()) return;`). The migration handles the live-DB changes. The seeder is for fresh installs only; it should be updated to match the final state for documentation purposes, but it does not need to run again.

---

## Files to create / modify

| Action | File |
|--------|------|
| MODIFY | `app/Models/Sector.php` — SoftDeletes, slug booted hook |
| MODIFY | `app/Models/FormField.php` — new fillable/casts, formatAnswer fallback |
| MODIFY | `app/Services/FormSubmissionService.php` — fieldIsVisible, checkbox validation, storeAnswers |
| MODIFY | `app/Http/Controllers/Admin/FormBuilderController.php` — system-managed guards |
| MODIFY | `app/Providers/AppServiceProvider.php` — register SectorObserver |
| MODIFY | `resources/views/pages/home.blade.php` — Fix 1 + Fix 2 |
| MODIFY | `resources/views/join/create.blade.php` — conditional_logic, checkbox array inputs, step validation |
| CREATE | `app/Observers/SectorObserver.php` |
| CREATE | `database/migrations/…_add_code_conditional_logic_is_system_managed_to_form_fields.php` |
| CREATE | `database/migrations/…_add_slug_soft_delete_to_sectors.php` |
| CREATE | `database/migrations/…_update_form_fields_seed_section3.php` |
| REVIEW | `resources/views/admin/form-builder/` — mark system-managed fields in UI |
