# SSBC — Syrian Saudi Business Council

Bilingual (English / Arabic) institutional website for the Syrian Saudi Business Council, built on Laravel 11 and deployable to Hostinger shared hosting.

## Stack

- Laravel 11 (PHP 8.2+)
- MySQL
- Blade + Tailwind CSS (Vite)
- Alpine.js (multi-step form)
- Built-in session auth (single seeded admin, no registration)
- Local disk storage via `storage/app/public` (symlinked)

## Local development

```bash
composer install
npm install
npm run build           # or `npm run dev` while developing

cp .env.example .env
php artisan key:generate

# Set DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env, then:
php artisan migrate --seed
php artisan storage:link

php artisan serve
```

The seeder creates:

- **Admin user** — `admin@ssbc.org` / `Admin1234!`
- **Site settings row** — with placeholder contact info, editable at `/admin/settings`

## Routing

- `/` → redirects to `/en` or `/ar` based on the request's `Accept-Language` header.
- `/{locale}/...` → all public pages (locale must be `en` or `ar`).
- `/admin/...` → admin panel (login at `/admin/login`).

| Public                          | Admin                         |
|---------------------------------|-------------------------------|
| `/en`, `/ar`                    | `/admin/dashboard`            |
| `/en/about`                     | `/admin/news`                 |
| `/en/news`, `/en/news/{slug}`   | `/admin/join`                 |
| `/en/join` (4-step form)        | `/admin/contact`              |
| `/en/contact`                   | `/admin/membership`           |
|                                 | `/admin/settings`             |

## Brand tokens

Defined in `tailwind.config.js`:

- `ssbc-green`   `#153e35` — primary surface (nav, hero, footer)
- `ssbc-gold`    `#daa900` — accent (lines, buttons, badges) only
- `ssbc-sage`    `#90aba0` — muted text
- `ssbc-beige`   `#f0e6dc` — alternating light section backgrounds
- `ssbc-light`   `#f4f5f7` — admin background
- `ssbc-dark`    `#1a1a2e` — body text

Fonts (loaded from Google Fonts): **El Messiri** (display) and **Noto Kufi Arabic** (body).

Visual rules enforced site-wide:

- No `rounded-*` on cards / buttons / inputs (only `rounded-full` for 4px dots).
- No `shadow-*` on cards or panels.
- No gradients, no illustrations.
- Gold is accent only — never a section background.

## i18n

- Translations live under `lang/en/*.php` and `lang/ar/*.php`.
- Locale is set by the `SetLocale` middleware from the `{locale}` route segment.
- Arabic pages render with `<html dir="rtl">`.
- The header has a plain text `EN | AR` switcher.

## File uploads

- Driver: `public` disk → `storage/app/public`, symlinked to `public/storage` by `php artisan storage:link`.
- News featured images → `news/{slug}/`.
- Membership documents → `membership/{uuid}/`.
- File limits: 8 MB. ID = jpg/png/pdf; company docs = pdf/doc/docx; company profile = pdf.

## Deployment to Hostinger shared hosting

1. Build assets locally: `npm run build` and commit `public/build/`.
2. Upload the repository to your hosting account.
3. Point the domain's document root to the `public/` directory.
4. Create a MySQL database in hPanel and copy credentials into `.env`.
5. SSH (or use Hostinger's terminal) and run:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force --seed
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
6. Sign in at `/admin/login` and change the admin password (via DB or re-seed).

## Project structure (highlights)

```
app/
  Http/
    Controllers/        # Public (Home, About, News, Join, Contact)
    Controllers/Admin/  # Admin panel (Dashboard, News, Join, Contact, Membership, Settings, Auth)
    Middleware/SetLocale.php
  Models/               # User, NewsPost, JoinSubmission, ContactSubmission, MembershipApplication, SiteSetting
database/
  migrations/           # users + sessions + cache, plus the 5 SSBC tables
  seeders/DatabaseSeeder.php
lang/{en,ar}/           # nav, common, home, about, news, join, contact, footer, admin
resources/
  css/app.css           # Tailwind + brand component classes (.ssbc-*)
  js/app.js             # Alpine bootstrap
  views/
    layouts/{app,admin}.blade.php
    partials/{header,footer,page-hero,news-card}.blade.php
    pages/{home,about}.blade.php
    news/{index,show}.blade.php
    join/{create,thanks}.blade.php
    contact/{create,thanks}.blade.php
    admin/...
routes/web.php
```

## What's intentionally NOT here (V1)

- No events page, no leadership directory, no member directory.
- No email notifications on form submissions.
- No rich text editor for news — plain textarea, HTML rendered as-is on the public page.
- No multi-user admin — one seeded admin.
- No pagination on admin lists.
