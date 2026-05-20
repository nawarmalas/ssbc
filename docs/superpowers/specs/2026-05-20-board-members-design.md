# Board Members Section — Design Spec
**Date:** 2026-05-20
**Status:** Approved

---

## Overview

Add an "أعضاء المجلس / Board Members" section to the home page, fully manageable from the admin dashboard. Each member displays a photo, bilingual name, and role. Hovering reveals a biography overlay. Mobile users tap to toggle the bio. Content is bilingual (Arabic + English). Admin-only management (no subadmin access).

---

## 1. Database & Model

### Migration: `create_board_members_table`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name_ar` | string | Arabic name |
| `name_en` | string | English name |
| `role_ar` | string | Arabic title/position |
| `role_en` | string | English title/position |
| `bio_ar` | text | Arabic biography (shown on hover) |
| `bio_en` | text | English biography (shown on hover) |
| `photo` | string, nullable | Stored path relative to `storage/app/public/` |
| `sort_order` | integer, default 0 | Controls display order on home page |
| `is_active` | boolean, default true | Show/hide toggle |
| `timestamps` | | created_at, updated_at |

### Model: `app/Models/BoardMember.php`

- Locale helper methods: `name()`, `role()`, `bio()` — return the field for `app()->getLocale()`, falling back to `ar`
- `photoUrl()` — returns full storage URL; returns a default placeholder path if `photo` is null
- Scope: `scopeActive($query)` — filters `is_active = true`, ordered by `sort_order` ascending

---

## 2. Admin Panel

### Routes (`routes/web.php`)

Added inside the existing `auth` middleware group, restricted to `admin.role:admin`:

```
GET    /admin/board-members           → BoardMemberController@index
GET    /admin/board-members/create    → BoardMemberController@create
POST   /admin/board-members           → BoardMemberController@store
GET    /admin/board-members/{id}/edit → BoardMemberController@edit
PUT    /admin/board-members/{id}      → BoardMemberController@update
DELETE /admin/board-members/{id}      → BoardMemberController@destroy
```

### Controller: `app/Http/Controllers/Admin/BoardMemberController.php`

Follows the `NewsController` pattern exactly:

- **`index`** — lists all members ordered by `sort_order`, paginated
- **`create` / `store`** — form + validation + image upload to `storage/app/public/board-members/`
- **`edit` / `update`** — same form pre-filled; replaces old photo on upload, deletes old file
- **`destroy`** — deletes record and associated photo file

Validation rules:
- `name_ar`, `name_en`, `role_ar`, `role_en`: required, string, max 255
- `bio_ar`, `bio_en`: required, string, max 1000
- `photo`: nullable, image, max 2MB, mimes: jpg, jpeg, png, webp
- `sort_order`: integer, min 0
- `is_active`: boolean

### Views: `resources/views/admin/board-members/`

- **`index.blade.php`** — table with: photo thumbnail (40×40 rounded), Arabic name, English name, role (AR), sort order, active badge, Edit / Delete actions. Uses existing `.ssbc-admin-table` classes.
- **`create.blade.php`** / **`edit.blade.php`** — shared form structure with:
  - Two-column grid: Arabic fields (right) | English fields (left)
  - Fields: name, role, bio (textarea)
  - Photo upload with current photo preview on edit
  - Sort order number input
  - Active checkbox
  - Submit + Cancel buttons using `.ssbc-btn` classes

### Sidebar Navigation (`resources/views/layouts/admin.blade.php`)

Add "أعضاء المجلس / Board Members" nav link in the admin-only section (same guard as the Dashboard and Users links). Uses `.ssbc-nav-link` and `.ssbc-nav-link-active` classes.

---

## 3. Frontend — Home Page Section

### Partial: `resources/views/pages/partials/board-members.blade.php`

Included in the home page view via `@include('pages.partials.board-members')`.

**Data:** Fetches `BoardMember::active()->get()` — active members ordered by `sort_order`. Passed from the home page controller.

**Section structure:**

```
<section> [bg: ssbc-beige #f0e6dc, padding: py-18]
  Eyebrow: "Board Members" [uppercase, ssbc-sage, tracking-widest]
  Heading: "أعضاء المجلس" [El Messiri, ssbc-green, text-3xl]
  Gold rule divider [ssbc-rule, centered]
  5-column grid [gap-5, max-w-5xl, mx-auto]
    × N member cards
</section>
```

**Responsive grid (Tailwind):**
- `grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5`

**Member card structure:**

```
<div> [bg-white, rounded-xl, overflow-hidden, shadow-sm, hover:shadow-lg, hover:-translate-y-1, transition]
  Photo wrap [relative, aspect-[3/4], overflow-hidden]
    <img> [w-full, h-full, object-cover, object-top]
    Bio overlay [absolute, bottom-[-100%], hover:bottom-0, transition-all, duration-350]
      Gold label: "نبذة مختصرة" / "About"
      Bio text in current locale
  Card footer [text-center, border-t border-ssbc-beige, py-3 px-3]
    Name in current locale [El Messiri, font-bold, text-sm]
    Role in current locale [text-xs, ssbc-sage]
```

**Mobile tap-to-reveal (Alpine.js):**

Each card has `x-data="{ open: false }"`. CSS `:hover` handles the overlay on desktop (no JS needed). On mobile, a `@click="open = !open"` listener on the card toggles `open`. The overlay has both the CSS hover rule and an Alpine `:class="{ '!bottom-0': open }"` binding — the `!` prefix ensures the Alpine state overrides the CSS default on touch. This keeps desktop behaviour pure CSS and mobile behaviour pure Alpine with no conflict.

**RTL:** The section inherits the page `dir` attribute from the locale layout. No additional RTL handling needed.

**Home page controller** (`app/Http/Controllers/HomeController.php` or equivalent): Add `BoardMember::active()->get()` to the data passed to the home view.

---

## 4. Images

The 5 member photos provided by the client are uploaded through the admin panel after the feature is built. No photos are hardcoded. A SVG placeholder is shown when `photo` is null.

---

## 5. Out of Scope

- Individual member profile pages
- Subadmin access to board members management
- Drag-and-drop reordering (sort order managed via number input)
- Member social links

---

## File Checklist

**New files:**
- `database/migrations/XXXX_create_board_members_table.php`
- `app/Models/BoardMember.php`
- `app/Http/Controllers/Admin/BoardMemberController.php`
- `resources/views/admin/board-members/index.blade.php`
- `resources/views/admin/board-members/create.blade.php`
- `resources/views/admin/board-members/edit.blade.php`
- `resources/views/pages/partials/board-members.blade.php`

**Modified files:**
- `routes/web.php` — add board member routes
- `resources/views/layouts/admin.blade.php` — add sidebar nav link
- Home page controller — pass `BoardMember::active()->get()` to view
- Home page Blade view — include the partial
