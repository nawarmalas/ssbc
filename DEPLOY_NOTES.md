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
- **No new composer or npm dependencies.** Compression is canvas-based inline
  JS (the admin form intentionally has no build step).
- Gallery/featured/block image limits are now **16 MB per image** (Laravel
  `max:16384`), max **10 gallery images per post** (enforced client-side,
  in validation, and as a total-count check on update).
- Legacy multipart image fields (`featured_image`, `gallery_images[]`,
  `block_image_*`) are still accepted server-side for backward compatibility,
  but the form no longer sends them.
