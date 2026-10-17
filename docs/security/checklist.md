---
title: Production Checklist
description: A security and operations checklist for running MediaFlow for Laravel in production.
---

# Production Checklist

## Access

- [ ] Keep `MEDIAFLOW_AUTH_ENABLED=true`.
- [ ] Limit access with roles, permissions, a gate or `MediaFlow::authorize()`. The Settings and Diagnostics pages can change storage credentials, so keep them to administrators.
- [ ] Add rate limiting, e.g. `MEDIAFLOW_CUSTOM_MIDDLEWARE=throttle:60,1`.
- [ ] Turn on `MEDIAFLOW_USER_SCOPED=true` if non-admin users share one library, and validate submitted media IDs against the current user.

## Files

- [ ] Store private uploads on a non-public disk with `MEDIAFLOW_PRIVATE_DISK`.
- [ ] Keep `allowed_mimes` as narrow as your app needs.
- [ ] Leave `sanitize_svg` on, or remove `image/svg+xml` if you do not need SVG.
- [ ] Raise `upload_max_filesize`, `post_max_size` and your web server body limit to at least `MEDIAFLOW_MAX_FILE_SIZE_KB`.

## Credentials and URLs

- [ ] Put cloud credentials in `.env` (`MEDIAFLOW_PROVIDER_*`). Values saved on the Settings page are stored in `mediaflow_settings`; they are never sent to the browser, but anyone with database access can read them.
- [ ] Configure trusted hosts and proxies so generated URLs use your real domain.

## Deploys and monitoring

- [ ] Run `php artisan config:cache` (or `php artisan optimize`) after installing or updating.
- [ ] Re-publish assets after every update: `php artisan vendor:publish --tag=mediaflow-assets --force`.
- [ ] Schedule `mediaflow:clean-temp` and monitor `mediaflow:verify-storage`; see [Artisan Commands](/reference/artisan).
