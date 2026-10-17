---
title: Diagnostics
description: Use MediaFlow's Diagnostics page and API to check PHP extensions, image support, the S3 driver and storage read/write health in Laravel.
---

# Diagnostics

The Diagnostics page at `/mediaflow/diagnostics` shows how MediaFlow sees your server and lets you test a storage disk.

## Environment report

`GET /mediaflow/diagnostics/info` returns:

| Field | Meaning |
| --- | --- |
| `php_version`, `laravel_version` | Runtime versions. |
| `gd_supported`, `imagick_supported` | Image extensions that are loaded. MediaFlow uses GD. |
| `webp_supported` | Whether GD can write WebP. Without it, images are kept as JPEG or PNG. |
| `s3_driver_installed` | Whether `league/flysystem-aws-s3-v3` is available for cloud providers. |
| `default_disk`, `temporary_disk`, `delivery_driver` | Active storage and delivery settings. |
| `max_file_size_kb`, `allowed_mimes_count` | Upload limits in effect. |
| `queue_processing`, `ui_enabled` | Reserved flags. |

## Storage probe

`POST /mediaflow/diagnostics/test` with `disk` writes a small probe file, reads it back to verify its contents, deletes it and reports the result. Only disks defined in `config/filesystems.php` are accepted.

```js
await fetch('/mediaflow/diagnostics/test', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
  body: JSON.stringify({ disk: 'public' }),
})
```

From PHP, `MediaFlow::testStorageConnection('public')` runs the same probe. For cloud credentials that are not saved yet, use the provider test on the [Settings page](/storage/cloud-providers#testing-a-connection).

::: warning Admin only
Diagnostics reveal server details. Keep MediaFlow restricted to administrators; see [Access Control](/security/access-control).
:::
