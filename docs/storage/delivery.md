---
title: Delivery URLs
description: Control how MediaFlow builds file URLs in Laravel, using disk URLs, a CDN domain, or expiring signed URLs for private files.
---

# Delivery URLs

Where a file is stored and how its URL is built are separate. `$media->url()` and `$media->thumbnailUrl()` ask the delivery resolver, which follows these rules:

1. **Private file, or the `signed` driver:** an expiring signed URL.
2. **`cdn` driver with a base URL:** `MEDIAFLOW_CDN_URL` + the storage key.
3. **Otherwise (`disk`):** the disk's own URL, from `Storage::disk()->url()`.

| `MEDIAFLOW_DELIVERY_DRIVER` | Result |
| --- | --- |
| `disk` (default) | `https://your-app.test/storage/media/2026/10/uuid.webp` |
| `cdn` | `https://cdn.example.com/media/2026/10/uuid.webp` |
| `signed` | Every URL expires after `MEDIAFLOW_SIGNED_EXPIRY` minutes. |

## CDN domain

```dotenv
MEDIAFLOW_DELIVERY_DRIVER=cdn
MEDIAFLOW_CDN_URL=https://media.example.com
```

Setting `MEDIAFLOW_PROVIDER_PUBLIC_URL` for a [cloud provider](/storage/cloud-providers) turns this on automatically.

## Private files and signed URLs

```php
// Stored on MEDIAFLOW_PRIVATE_DISK when it is set
$media = MediaFlow::upload($file, ['visibility' => 'private']);

$media->url();                 // signed automatically for private media
$media->thumbnailUrl('thumb'); // .../mediaflow/delivery/12?variant=thumb&expires=...&signature=...
$media->temporaryUrl(10);      // explicit lifetime in minutes
```

- On S3-compatible disks, a native pre-signed URL is returned.
- On local disks, MediaFlow signs its own `/mediaflow/delivery/{media}` route, which streams the file after checking the signature. This route is public; the signature is the access check.

The delivery route serves images, video and audio inline. Everything else, including SVG and PDF, is sent as a download with a sandboxing Content-Security-Policy and a safe `Content-Disposition` header.

::: warning Keep private files off the public disk
If private files land on the `public` disk, anyone who learns the path can fetch them through the `storage:link` symlink. Set `MEDIAFLOW_PRIVATE_DISK=local`, or a private bucket, so they never do.
:::

## Local development URLs

On local disks, generated URLs are rewritten to the request's host only for localhost development. In production, URLs come from your disk configuration and `APP_URL`, so a spoofed `Host` header cannot change them. Configure trusted proxies so your real domain is used behind a load balancer.
