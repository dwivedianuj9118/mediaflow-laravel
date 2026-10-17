---
title: Requirements
description: PHP, Laravel, database and PHP extension requirements for MediaFlow for Laravel.
---

# Requirements

| Component | Supported | Notes |
| --- | --- | --- |
| PHP | 8.2, 8.3, 8.4, 8.5 | CI runs the test suite on PHP 8.2, 8.3 and 8.4. |
| Laravel | 11.x, 12.x, 13.x | Package auto-discovery registers the service provider and the `MediaFlow` facade. |
| Database | SQLite 3.35+, MySQL 8.0+, PostgreSQL 14+ | Stores media, folders, variants, upload sessions and settings. |
| `ext-fileinfo` | Required | Detects the real MIME type of every upload. Declared in `composer.json`. |
| `ext-gd` with WebP | Recommended | Needed for thumbnails, WebP conversion, EXIF stripping and optimization. Without it, files still upload but no image variants are made. |
| `ext-exif` | Recommended | Keeps JPEG orientation correct when metadata is stripped. |

`league/flysystem-aws-s3-v3` is installed with MediaFlow, so the S3-compatible [cloud providers](/storage/cloud-providers) work without extra packages.

::: info Image driver
Only the GD driver ships today. `MEDIAFLOW_IMAGE_DRIVER=imagick` is a reserved value. To use Imagick or another engine, bind your own `ImageProcessor`; see [Extending](/reference/extending).
:::

## Server limits

PHP and your web server must accept files at least as large as `MEDIAFLOW_MAX_FILE_SIZE_KB` (20 MB by default). Raise these to match:

- `upload_max_filesize` and `post_max_size` in `php.ini`
- the request body limit of your web server (for example `client_max_body_size` in nginx)

The [Diagnostics page](/reference/diagnostics) shows which extensions are available on your server.
