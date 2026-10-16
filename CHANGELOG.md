# Changelog

## Unreleased — security & reliability fixes

### ⚠️ Upgrade notes (behaviour changes)
- **Authentication is now required by default** (`MEDIAFLOW_AUTH_ENABLED` defaults to `true`). Previously every management route — upload, delete, settings (including cloud credentials) and diagnostics — was open to anonymous visitors. Set `MEDIAFLOW_AUTH_ENABLED=false` only for local demos.
- **Config is a security floor for access control.** Settings saved from the web UI can no longer switch authentication off or replace roles/permissions/guard/gate defined in config or `.env`. Opt back in with `MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE=true`.
- **Stored file extensions come from the detected MIME type**, not the client filename (`notes.html` with plain-text content is stored as `.txt`). HTML/XML and more script extensions are blocked.
- `POST /mediaflow/settings` only accepts known keys and validates values (unknown keys are ignored, invalid values return 422).
- The `storage_quota_mb` setting no longer overwrites `upload.max_file_size_kb` (the units differed: MB vs KB).
- Republish assets: `php artisan vendor:publish --tag=mediaflow-assets --force`.

### Security
- Fixed stored XSS through theme colour settings rendered unescaped into `<style>` on the manager page; colours are now validated as hex on save and on output.
- Fixed XSS in the media grid, inspector, picker, uploader preview and input component: file names and URLs were inserted into HTML without escaping quotes (or at all).
- Cloud provider secret is no longer rendered into the settings page HTML or returned by the settings JSON API; a masked placeholder is shown and keeps the stored value when saved.
- The provider connection test refuses non-http(s) endpoints and cloud-metadata / link-local addresses (SSRF).
- Diagnostics storage probe only accepts disks defined in `config/filesystems.php`.
- Stronger SVG filtering (event handlers, `foreignObject`, external `href`, entities, encoded payloads).
- Decompression-bomb guard (`max_image_megapixels`, default 50).
- Signed delivery stream serves only images/video/audio inline, sends a sandboxing CSP and builds `Content-Disposition` safely.
- Local disk URLs are only rewritten to the request host for localhost development, so a spoofed `Host` header cannot change generated URLs in production.
- Unexpected upload/optimization errors are logged instead of returning raw exception messages to the browser.
- `scope.user_scoped` is now enforced through a global `OwnerScope` on `Media` and `MediaFolder` (it was documented but not implemented).
- New `private_disk` option so private uploads never land on the public disk.

### Production / no-dev hardening
- `league/flysystem-aws-s3-v3` is now listed under `suggest` (it was only a dev dependency). Without it, the provider test explains what to install and a saved cloud provider no longer switches uploads to a disk that cannot work.
- If the app's config cache predates MediaFlow, the package now loads its own safe defaults (previously every MIME type and extension was allowed and no thumbnails were made) and logs a warning to re-run `config:cache`.
- A failing cache store no longer takes the MediaFlow UI down (settings fall back to the database).
- Architecture diagrams used by the docs page are now published with `--tag=mediaflow-assets` (they 404'd and fell back to GitHub Pages).
- Diagnostics report WebP and S3-driver availability; `ext-fileinfo` is declared in composer.json.

### Fixes
- EXIF/GPS metadata is now actually stripped from JPEG/PNG/WebP uploads (`images.strip_exif` was documented but never applied); JPEG orientation is preserved.
- Listing private images on a local disk crashed (variant URLs threw); variants now fall back to signed delivery URLs (`?variant=thumb`).
- The `signed` delivery driver is now honoured.
- Renaming or moving a folder now updates the slug and the paths of all descendant folders; deleting a folder recomputes moved children's paths; duplicate names are rejected on rename/move.
- The media picker showed blank file names (JSON lacked `original_name`).
- Upload opened the temp file with `r+` (fails on read-only temp dirs).
- Optimizing to JPEG turned transparent areas black; storage write failures during optimize are detected.
- Max file size and default disk saved on the Settings page are now applied.
- Logout links used GET (Laravel's logout route is POST).
- `MEDIAFLOW_AUTH_GUARD` (as documented) is accepted as an alias of `MEDIAFLOW_GUARD`.

### Documentation
- Corrected wrong examples: `optimize()` signature and return shape, `MediaFlow::authorize()` callback arguments, `temporaryUrl()`, `humanSize()`, env variable names, REST parameters, `exists:mediaflow_media,id`.
- Added: complete env reference, full REST API table, events & JS events, Artisan commands and scheduling, custom adapters/processors, per-user scoping, security checklist, testing & troubleshooting; fixed broken sidebar anchors.
