---
title: Environment Variables
description: Every MediaFlow for Laravel environment variable with its default value and the config/mediaflow.php key it controls.
---

# Environment Variables

All options live in `config/mediaflow.php`. Most can be set from `.env`; the table lists each variable, its default and the config key it maps to. After changing `.env` in production, run `php artisan config:cache`.

## Routes

| Variable | Default | Config key | Purpose |
| --- | --- | --- | --- |
| `MEDIAFLOW_ROUTE_PREFIX` | `mediaflow` | `route_prefix` | URL prefix for every MediaFlow page and endpoint. |
| `MEDIAFLOW_MIDDLEWARE` | `web` | `middleware` | Comma-separated base middleware. The access-control middleware is always added after it. |

## Access control

| Variable | Default | Config key | Purpose |
| --- | --- | --- | --- |
| `MEDIAFLOW_AUTH_ENABLED` | `true` | `auth.enabled` | Require a signed-in user on management routes. |
| `MEDIAFLOW_GUARD` | default guard | `auth.guard` | Guard to authenticate with. `MEDIAFLOW_AUTH_GUARD` is accepted as an alias. |
| `MEDIAFLOW_ROLES` | none | `auth.roles` | Comma-separated roles; any one grants access. |
| `MEDIAFLOW_PERMISSIONS` | none | `auth.permissions` | Comma-separated permissions; any one grants access. |
| `MEDIAFLOW_GATE` | none | `auth.gate` | Gate ability that must allow the user. |
| `MEDIAFLOW_CUSTOM_MIDDLEWARE` | none | `auth.custom_middleware` | Extra middleware appended to every route, e.g. `verified,throttle:60,1`. |
| `MEDIAFLOW_AUTH_ALLOW_UI_OVERRIDE` | `false` | `auth.allow_ui_override` | Let the Settings page relax or replace the rules above. |
| `MEDIAFLOW_USER_SCOPED` | `false` | `scope.user_scoped` | Give every user a private library. |

Details: [Access Control](/security/access-control) and [Per-User Scoping](/security/user-scoping).

## Database tables

| Variable | Default | Config key |
| --- | --- | --- |
| `MEDIAFLOW_TABLE_PREFIX` | `mediaflow_` | `table_prefix` |
| `MEDIAFLOW_TABLE_MEDIA` | `{prefix}media` | `tables.media` |
| `MEDIAFLOW_TABLE_FOLDERS` | `{prefix}folders` | `tables.folders` |
| `MEDIAFLOW_TABLE_VARIANTS` | `{prefix}variants` | `tables.variants` |
| `MEDIAFLOW_TABLE_UPLOAD_SESSIONS` | `{prefix}upload_sessions` | `tables.upload_sessions` |
| `MEDIAFLOW_TABLE_SETTINGS` | `{prefix}settings` | `tables.settings` |

Set these **before** running the migrations. See [Database Tables](/config/database).

## Storage

| Variable | Default | Config key | Purpose |
| --- | --- | --- | --- |
| `MEDIAFLOW_DISK` | `public` | `default_disk` | Disk for public uploads. Set automatically to `mediaflow_cloud` when a cloud provider is active. |
| `MEDIAFLOW_PRIVATE_DISK` | `default_disk` | `private_disk` | Disk for uploads with `visibility=private`. Use a non-public disk such as `local`. |
| `MEDIAFLOW_TEMP_DISK` | `local` | `temporary_disk` | Disk for temporary upload files. |

## Cloud provider

| Variable | Default | Config key | Purpose |
| --- | --- | --- | --- |
| `MEDIAFLOW_PROVIDER` | `local` | `provider.name` | Preset ID: `cloudflare_r2`, `aws`, `contabo`, `digitalocean`, `wasabi`, `backblaze`, `gcs`, `azure`, `cloudinary`, `minio`, or `local` for none. |
| `MEDIAFLOW_PROVIDER_KEY` | none | `provider.key` | Access key ID. |
| `MEDIAFLOW_PROVIDER_SECRET` | none | `provider.secret` | Secret access key. |
| `MEDIAFLOW_PROVIDER_BUCKET` | none | `provider.bucket` | Bucket or container name. |
| `MEDIAFLOW_PROVIDER_ACCOUNT_ID` | none | `provider.account_id` | Cloudflare account ID for R2. Falls back to `CLOUDFLARE_ACCOUNT_ID`, then `CLOUDFLARE_R2_ACCOUNT_ID`. |
| `MEDIAFLOW_PROVIDER_REGION` | preset default | `provider.region` | Region, e.g. `us-east-1`, `nyc3`, `eu2`. |
| `MEDIAFLOW_PROVIDER_ENDPOINT` | preset template | `provider.endpoint` | Full S3 endpoint URL, when the preset cannot work it out. |
| `MEDIAFLOW_PROVIDER_PUBLIC_URL` | none | `provider.public_url` | Public or CDN base URL. When set, delivery switches to the `cdn` driver. |
| `MEDIAFLOW_PROVIDER_PATH_STYLE` | preset default | `provider.path_style` | Force path-style bucket addressing. |

See [Cloud Providers](/storage/cloud-providers).

## Upload limits

| Variable | Default | Config key | Purpose |
| --- | --- | --- | --- |
| `MEDIAFLOW_MAX_FILE_SIZE_KB` | `20480` (20 MB) | `max_file_size_kb` | Largest accepted file. The Settings page can override it. |
| `MEDIAFLOW_MAX_IMAGE_MEGAPIXELS` | `50` | `max_image_megapixels` | Rejects larger images to protect GD from decompression bombs. |

`allowed_mimes`, `blocked_extensions` and `sanitize_svg` are set in the config file only. See [Upload Rules](/config/uploads).

## Images

| Variable | Default | Config key | Purpose |
| --- | --- | --- | --- |
| `MEDIAFLOW_IMAGE_DRIVER` | `gd` | `images.driver` | Only `gd` ships today. |
| `MEDIAFLOW_DEFAULT_FORMAT` | `webp` | `images.default_format` | Format uploads are converted to: `webp`, `jpeg`, `png` or `keep`. |
| `MEDIAFLOW_DEFAULT_QUALITY` | `82` | `images.default_quality` | Encoding quality, 30 to 100. |
| `MEDIAFLOW_AUTO_OPTIMIZE` | `true` | `images.auto_optimize` | Convert and downscale images on upload. |
| `MEDIAFLOW_MAX_WIDTH` | `2560` | `images.max_width` | Larger images are scaled down on upload. |
| `MEDIAFLOW_MAX_HEIGHT` | `2560` | `images.max_height` | Larger images are scaled down on upload. |
| `MEDIAFLOW_QUEUE_PROCESSING` | `false` | `images.queue_processing` | Reserved; processing is synchronous today. |
| `MEDIAFLOW_QUEUE` | `default` | `images.queue_name` | Reserved for queued processing. |

`strip_exif`, `generate_thumbnails` and `presets` are set in the config file. See [Image Processing](/config/images).

## Delivery

| Variable | Default | Config key | Purpose |
| --- | --- | --- | --- |
| `MEDIAFLOW_DELIVERY_DRIVER` | `disk` | `delivery.driver` | `disk` (the disk's URL), `cdn` (prefix with the CDN URL) or `signed` (always expiring URLs). |
| `MEDIAFLOW_CDN_URL` | none | `delivery.base_url` | Base URL for the `cdn` driver. |
| `MEDIAFLOW_SIGNED_EXPIRY` | `30` | `delivery.signed_url_expiration_minutes` | Lifetime of signed URLs, in minutes. |

See [Delivery URLs](/storage/delivery).

## Interface

| Variable | Default | Config key | Purpose |
| --- | --- | --- | --- |
| `MEDIAFLOW_SHOW_HEADER` | `true` | `ui.show_header` | Show the header bar on the built-in pages. |
| `MEDIAFLOW_DEV_NAV` | `false` | `ui.show_dev_nav` | Show links to the docs and pipeline pages in the header. |

Other `ui` keys in the config file: `animations`, `show_pipeline` (default for the components' `pipeline` option) and `items_per_page` (24). `enabled`, `allow_delete`, `allow_rename` and `allow_move` are reserved and not enforced yet.

## Complete example

```dotenv
# Routes and access
MEDIAFLOW_ROUTE_PREFIX=admin/media
MEDIAFLOW_AUTH_ENABLED=true
MEDIAFLOW_ROLES=admin,editor
MEDIAFLOW_CUSTOM_MIDDLEWARE=throttle:60,1

# Storage
MEDIAFLOW_DISK=public
MEDIAFLOW_PRIVATE_DISK=local

# Cloudflare R2 behind a custom domain
MEDIAFLOW_PROVIDER=cloudflare_r2
MEDIAFLOW_PROVIDER_ACCOUNT_ID=your-32-character-account-id
MEDIAFLOW_PROVIDER_KEY=your-access-key-id
MEDIAFLOW_PROVIDER_SECRET=your-secret-access-key
MEDIAFLOW_PROVIDER_BUCKET=my-bucket
MEDIAFLOW_PROVIDER_PUBLIC_URL=https://cdn.example.com

# Images
MEDIAFLOW_DEFAULT_FORMAT=webp
MEDIAFLOW_DEFAULT_QUALITY=82
MEDIAFLOW_MAX_FILE_SIZE_KB=20480
```
