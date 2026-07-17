# Deploy Notes — News Image Upload Overhaul (2026-07-16)

The admin news form no longer sends all images in one giant multipart POST.
Every image (featured, gallery, content-block) is compressed in the browser
(max 1920 px longest edge, ~0.82 quality, files under 500 KB skipped) and
uploaded **one per request** (max 2 in parallel) to:

    POST /admin/news/upload-image      (auth + admin.permission:news_write,news_publish)

Uploads land in `storage/app/public/news/_staging/` and the final Save request
carries only text fields + staged paths, so saving a post is near-instant.

## Server-level requirements on the host

### 1. PHP limits (already shipped in this repo)

| Setting | Shipped value | Minimum needed now | File |
|---|---|---|---|
| `upload_max_filesize` | 100M | 16M | `public/.user.ini` (FPM/CGI) and `public/.htaccess` (mod_php) |
| `post_max_size` | 110M | 24M | same |
| `max_file_uploads` | 20 | 2 | `public/.user.ini` |
| `max_execution_time` | 300 | 60 | same |
| `max_input_time` | 300 | 60 | same |
| `memory_limit` | 256M | 128M | same |

The shipped values are intentionally **higher** than the news uploads need
because the membership join form accepts attachments up to 100 MB — do **not**
lower them. Since every news upload request now carries at most one ≤16 MB
image, 24M `post_max_size` would be enough for news alone.

`.user.ini` changes are re-read every `user_ini.cache_ttl` seconds
(default 300) — allow ~5 minutes or reload PHP-FPM after deploying.

### 2. Web-server body-size cap (must be set on the host — cannot be set from the app)

- **nginx**: `client_max_body_size 110m;` (matches the join-form limit; the
  news flow alone needs only ≥ 24m). Default is 1m, which causes 413s.
- **Apache**: `LimitRequestBody 115343360` (or 0 = unlimited). Usually
  already fine on shared hosting.

### 3. Timeouts

Each request now processes a single image, so no long-running requests are
expected. If the host allows it, keep:

- PHP-FPM `request_terminate_timeout` ≥ 120s
- nginx `fastcgi_read_timeout` / Apache `ProxyTimeout` ≥ 120s

(The old 503s came from one request handling up to 10 × 8 MB images; that
request shape no longer exists.)

### 4. Storage symlink

News images are stored on the `public` disk. The host must have the symlink:

    php artisan storage:link

(`public/storage` → `storage/app/public`). Already required by the existing
news feature — nothing new, just don't lose it when unzipping a build.

### 5. Staging directory cleanup

`storage/app/public/news/_staging/` holds images uploaded but not yet attached
to a saved post. The upload endpoint opportunistically deletes staged files
older than 24 h on each new upload, so no cron job is required. If you ever
want to clear it manually: it is always safe to delete files older than a day.

### 6. Caches after deploy

A new route (`admin/news/upload-image`) and changed Blade views ship with this
release. After unzipping the build on the host, clear cached artifacts:

    php artisan route:clear && php artisan view:clear && php artisan config:clear

(or re-run the usual `php artisan optimize` if that's part of the deploy.)

## Decisions / notes

- **No server-side thumbnail generation was added.** Client-side compression
  already caps images at 1920 px / ~0.82 quality before upload, which is the
  size the public gallery serves. Adding a PHP image library (Intervention/GD
  processing) was deliberately avoided — the host is resource-limited shared
  hosting and the build zip carries the vendor dir.
- ~~No new composer or npm dependencies. Compression is canvas-based inline
  JS.~~ **Superseded (2026-07-17):** images are now converted to **WebP in
  the browser** using the Squoosh WASM codec (`@jsquash/webp`, bundled by
  Vite into `public/build/` — the server still needs no build step). The
  canvas pipeline remains as an automatic fallback. See the WebP section
  below.
- Gallery/featured/block image limits are now **16 MB per image** (Laravel
  `max:16384`), max **10 gallery images per post** (enforced client-side,
  in validation, and as a total-count check on update).
- Legacy multipart image fields (`featured_image`, `gallery_images[]`,
  `block_image_*`) are still accepted server-side for backward compatibility,
  but the form no longer sends them.

---

# Deploy Notes — WebP uploads, lazy loading & Lighthouse pass (2026-07-17)

## What shipped

1. **In-browser WebP conversion for every admin-uploaded image** (news
   featured/gallery/content-block images, board-member photos, settings hero).
   Conversion runs in the admin's browser — Squoosh WASM encoder
   (`@jsquash/webp`, bundled locally in `public/build/`, no CDN), falling back
   to `canvas.toBlob('image/webp')`, then to compressed JPEG on browsers with
   no WebP encoder. Max 1920 px longest edge, quality ~80, renamed to
   `<basename>.webp`; if conversion can't beat the original size the smaller
   original is kept. The admin sees the saving ("4.2 MB → 310 KB") next to
   each file.
2. **Lazy loading everywhere**: all public + admin list images have
   `loading="lazy" decoding="async"` except above-the-fold/LCP images (home
   hero, article featured image, first news-index card) which are
   `loading="eager" fetchpriority="high"`. Content-block images render with
   explicit `width`/`height` (cached via `App\Support\ImageDimensions`).
3. **Self-hosted web fonts**: El Messiri + Noto Kufi Arabic woff2 files now
   live in `public/fonts/` (`font-display: optional` + per-locale preloads).
   **The site no longer loads fonts from fonts.googleapis.com at runtime**
   (only the admin news editor still pulls CKEditor + Cairo from CDNs).
4. New static files that MUST be deployed: `public/fonts/*.woff2`,
   `public/site.webmanifest`, `public/favicon-16x16.png`,
   `public/favicon-32x32.png`, `public/apple-touch-icon.png`,
   `public/android-chrome-{192x192,512x512}.png`, and the rebuilt
   `public/build/` directory (includes the WebP WASM assets
   `webp_enc*.wasm`).

## Server-level settings still needed on the host (cannot be set from the app)

Lighthouse flags these two on every page; both are host config, not code:

### 1. Text compression (gzip/brotli)

Compressing HTML/CSS/JS is worth ~65 KB on the CSS alone (77 KB → 12 KB).

- **Apache / LiteSpeed shared hosting** — add to the site config or the
  top-level `.htaccess` (NOT shipped in the repo because `public/.htaccess`
  is overwritten by deploys; confirm the host panel has "gzip/brotli
  compression" enabled, or add):

      <IfModule mod_deflate.c>
          AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/json image/svg+xml font/woff2
      </IfModule>

- **nginx**:

      gzip on;
      gzip_types text/html text/css application/javascript application/json image/svg+xml;
      # woff2 and webp are already compressed — do not add them

### 2. Long-lived cache headers for static assets

Everything under `public/build/` is content-hashed (safe to cache forever);
fonts and logos are stable.

- **Apache**:

      <IfModule mod_expires.c>
          ExpiresActive On
          ExpiresByType text/css "access plus 1 year"
          ExpiresByType application/javascript "access plus 1 year"
          ExpiresByType font/woff2 "access plus 1 year"
          ExpiresByType image/webp "access plus 30 days"
          ExpiresByType image/jpeg "access plus 30 days"
          ExpiresByType image/png "access plus 30 days"
      </IfModule>

- **nginx**:

      location /build/  { expires 1y; add_header Cache-Control "public, immutable"; }
      location /fonts/  { expires 1y; add_header Cache-Control "public, immutable"; }
      location /storage/ { expires 30d; }

## MIME type check

Ensure the host serves `.webp` as `image/webp` and `.wasm` as
`application/wasm` (default on any modern Apache/nginx; some very old shared
hosts need `AddType application/wasm .wasm` in `.htaccess`).

## Lighthouse results (local, devtools throttling, 2026-07-17)

| Page | Device | Perf | A11y | Best Practices | SEO |
|---|---|---|---|---|---|
| Home | desktop | 100 | 95 | 100 | 100 |
| Home | mobile | 88 | 96 | 100 | 100 |
| News index | desktop | 100 | 95 | 100 | 100 |
| News index | mobile | 91 | 95 | 100 | 100 |
| Article (gallery) | desktop | 100 | 95 | 100 | 100 |
| Article (gallery) | mobile | 91 | 95 | 100 | 100 |

Reports live in `lighthouse-reports/final-*.report.html`. Local runs had NO
gzip and NO cache headers (php artisan serve) — production numbers with the
host settings above will only be better.

## Decisions / notes (2026-07-17)

- Old jpg/png uploads are untouched and still render; only new uploads become
  WebP. Validation stays strict: jpeg/png/webp only (svg/gif/bmp now rejected
  where the loose `image` rule previously allowed them).
- Brand gold/sage text colors fail WCAG AA on light backgrounds; darker
  variants (`ssbc-gold-deep` #7d6400, `ssbc-sage-deep` #4e6a5e) are applied
  only on white/beige contexts — dark green sections keep the original brand
  colors.
- `font-display: optional` means a first-ever visit on a very slow connection
  renders with the metric-matched Arial fallback instead of a late font swap
  (zero layout shift); the woff2s are preloaded and cached, so brand fonts
  show on effectively all subsequent views.
