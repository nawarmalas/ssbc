# Sectors Section — Design Spec
**Date:** 2026-05-20
**Status:** Approved

---

## Overview

Add a fully admin-managed "القطاعات الاقتصادية / Economic Sectors" section to the home page, seeded with 15 bilingual sectors from the SSBC Strategic Vision 2026–2030 document. Each sector displays a name and description. The section is visually identical to the existing Strategic Pillars section (same CSS classes and grid layout). All content is managed from the admin dashboard; frontend pulls live from the database.

---

## 1. Database & Model

### Migration: `create_sectors_table`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name_ar` | string | Arabic sector name |
| `name_en` | string | English sector name |
| `description_ar` | text | Arabic description |
| `description_en` | text | English description |
| `sort_order` | integer, default 0 | Controls display order |
| `is_active` | boolean, default true | Show/hide toggle |
| `timestamps` | | created_at, updated_at |

### Model: `app/Models/Sector.php`

- Locale helpers: `name()`, `description()` — return the field for `app()->getLocale()`, falling back to `ar`
- Scope: `scopeActive($query)` — filters `is_active = true`, ordered by `sort_order` ascending
- Fillable: all columns except `id` and timestamps

### Seeder: `database/seeders/SectorSeeder.php`

Seeds all 15 sectors with Arabic and English names and descriptions from the SSBC Strategic Vision PDF. Registered in `DatabaseSeeder`. Uses `updateOrCreate` on `name_en` so it is safe to re-run.

The 15 sectors (in order):

| sort_order | name_ar | name_en |
|---|---|---|
| 1 | القطاع الزراعي والثروة الحيوانية | Agriculture and Livestock |
| 2 | قطاع المال والمصارف والتأمين | Finance Banking and Insurance |
| 3 | قطاع الصناعة | Industry and Manufacturing |
| 4 | قطاع الصادرات البينية والتجارة | Intraregional Trade and Exports |
| 5 | قطاع النفط والثروة المعدنية | Oil and Mineral Resources |
| 6 | قطاع الكهرباء والمياه | Electricity and Water |
| 7 | قطاع الصحة | Health and Pharmaceuticals |
| 8 | قطاع الإنشاء والتطوير العقاري | Real Estate Development and Construction |
| 9 | قطاع التعليم والتدريب | Education and Training |
| 10 | قطاع السياحة | Tourism |
| 11 | قطاع الدراما والميديا | Drama and Media |
| 12 | قطاع العمل التنموي | Development Work |
| 13 | قطاع النقل والخدمات اللوجستية | Transport and Logistics |
| 14 | قطاع الاتصالات وتقنية المعلومات وحاضنات ومسرعات الأعمال | Telecommunications IT and Business Incubators |
| 15 | قطاع الخدمات والاستشارات | Services and Consulting |

---

## 2. Admin Panel

### Routes (`routes/web.php`)

Added inside the existing `auth` + `admin.role:admin` middleware group:

```
GET    /admin/sectors           → SectorController@index
GET    /admin/sectors/create    → SectorController@create
POST   /admin/sectors           → SectorController@store
GET    /admin/sectors/{sector}/edit → SectorController@edit
PUT    /admin/sectors/{sector}  → SectorController@update
DELETE /admin/sectors/{sector}  → SectorController@destroy
```

Uses `Route::resource('sectors', SectorController::class)->except(['show'])`.

### Controller: `app/Http/Controllers/Admin/SectorController.php`

Follows the `BoardMemberController` pattern exactly. No file uploads.

Validation rules:
- `name_ar`, `name_en`: required, string, max 255
- `description_ar`, `description_en`: required, string, max 1000
- `sort_order`: integer, min 0
- `is_active`: nullable, boolean

`store()` and `update()` both use `$request->boolean('is_active')` to handle unchecked checkbox.

### Views: `resources/views/admin/sectors/`

- **`index.blade.php`** — table with: Arabic name, English name, description preview (truncated), sort order, active badge, Edit / Delete actions. Uses `.ssbc-admin-table` classes.
- **`_form.blade.php`** — shared form partial:
  - Two-column grid: Arabic fields (right) | English fields (left)
  - Fields: name (text input), description (textarea)
  - Sort order number input
  - Active checkbox using `@checked(old('is_active', $sector->is_active ?? true))`
  - Submit + Cancel buttons using `.ssbc-btn` classes
  - `@method('PUT')` on its own line in edit form
  - Includes `partials.admin.confirm-delete` on edit
- **`create.blade.php`** / **`edit.blade.php`** — thin wrappers including `_form`

### Sidebar Navigation (`resources/views/layouts/admin.blade.php`)

Add "القطاعات / Sectors" nav link in the admin-only section (same guard as Board Members and Dashboard links). Uses `.ssbc-nav-link` and `.ssbc-nav-link-active` classes.

---

## 3. Frontend — Home Page Section

### Partial: `resources/views/pages/partials/sectors.blade.php`

Included in the home page view via `@include('pages.partials.sectors')`, placed immediately **after** the Strategic Pillars section and **before** the Board Members section.

**Data:** `Sector::active()->get()` — active sectors ordered by `sort_order`. Passed from `HomeController`.

**Section structure** — identical pattern to Strategic Pillars:

```
<section class="bg-ssbc-beige"> (beige background to alternate with white Pillars section)
  .ssbc-container py-20
    .ssbc-rule
    <p class="ssbc-eyebrow"> — "Economic Sectors" (from SiteSettings)
    <h2> — "القطاعات الاقتصادية" (from SiteSettings, El Messiri, ssbc-green)
    <p> — body text (from SiteSettings)
    grid: md:grid-cols-2 lg:grid-cols-3 gap-10
      @foreach($sectors as $sector)
        <div class="ssbc-pillar-card">
          <h3> — $sector->name()
          <p> — $sector->description()
        </div>
```

Wrapped in `@if($sectors->isNotEmpty())` guard.

**RTL:** Inherits page `dir` attribute. No additional handling needed.

### Site Settings — Sectors Copy

Add a `sectors` block to the `HOME_FIELDS` schema in `SettingsController` with:
- `sectors.eyebrow` — e.g. "Economic Sectors" / "القطاعات الاقتصادية"
- `sectors.heading` — e.g. "القطاعات الاقتصادية"
- `sectors.body` — introductory paragraph

These appear as editable fields in the **Homepage** tab of Site Customization, following the same schema-driven pattern as `pillars.*`.

### HomeController (`app/Http/Controllers/HomeController.php`)

Add `$sectors = \App\Models\Sector::active()->get();` to the data passed to the home view, alongside existing `$posts` and `$boardMembers`.

---

## 4. Out of Scope

- The join form's "Sectors of Operation" checkbox field (`form_fields` table) is managed separately through the existing Form Builder admin. Auto-syncing it with the `sectors` table requires changes to the form renderer and is deferred. Admin can update join form sector options manually through the Form Builder.
- Individual sector detail pages
- Sector icons or images
- Drag-and-drop reordering (sort order managed via number input)

---

## File Checklist

**New files:**
- `database/migrations/XXXX_create_sectors_table.php`
- `database/seeders/SectorSeeder.php`
- `app/Models/Sector.php`
- `app/Http/Controllers/Admin/SectorController.php`
- `resources/views/admin/sectors/index.blade.php`
- `resources/views/admin/sectors/_form.blade.php`
- `resources/views/admin/sectors/create.blade.php`
- `resources/views/admin/sectors/edit.blade.php`
- `resources/views/pages/partials/sectors.blade.php`

**Modified files:**
- `routes/web.php` — add sector resource routes
- `resources/views/layouts/admin.blade.php` — add sidebar nav link
- `app/Http/Controllers/Admin/SettingsController.php` — add `sectors.*` fields to `HOME_FIELDS` schema
- `app/Http/Controllers/HomeController.php` — pass `Sector::active()->get()` to view
- `resources/views/pages/home.blade.php` — include sectors partial
