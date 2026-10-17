---
title: Introduction
description: What MediaFlow for Laravel is, what it includes, and how its storage, catalog and delivery layers fit together.
---

# Introduction

MediaFlow is a media management package for Laravel. It handles everything between a user choosing a file and your app showing it: the upload field, validation, image optimization, storage on a local disk or a cloud bucket, a browsable media library, and the URL you finally render.

It keeps two concerns apart:

- **Where files are stored.** Local disks, Amazon S3, Cloudflare R2, MinIO and other S3-compatible services, through storage adapters.
- **How files are delivered.** The disk's own URL, a CDN domain, or a time-limited signed URL, through a delivery resolver.

Because the two are separate, you can move from a local disk to R2 behind a CDN without changing the code that stores or displays media.

## What's included

| Feature | What you get |
| --- | --- |
| Blade components | `<x-mediaflow-input>` for forms, `<x-mediaflow::uploader>` for batch uploads and `<x-mediaflow::manager>` for the media library. |
| Media library | Folder tree, search, type filters, inspector panel, rename, move, bulk actions and a picker modal. |
| Image processing | WebP conversion, downscaling, EXIF/GPS stripping and thumbnail presets using the GD extension. |
| Cloud storage | Ten provider presets that configure an S3 disk for you, plus a connection test. |
| Delivery | Disk URLs, CDN prefixing and signed streaming for private files. |
| Access control | Login required by default, plus guards, roles, permissions, gates, a closure hook and per-user libraries. |
| Admin pages | Media manager, Settings, Diagnostics and an upload pipeline visualizer. |
| Operations | Events, Artisan commands for cleanup and storage checks, and extension points for custom adapters. |

The browser code is plain JavaScript and CSS published with `vendor:publish`. There is no npm build, Tailwind, Alpine or external CDN involved.

## Architecture

<div class="diagram">

![MediaFlow architecture: storage adapters, the media catalog and delivery URL resolution](/images/mediaflow_architecture.svg)

</div>

A request flows through three layers:

1. **Intake.** The upload controller and `MediaFlow::upload()` validate the file and pass it to the upload pipeline.
2. **Catalog.** The `Media`, `MediaFolder` and `MediaVariant` models record each file, its folder and its generated sizes.
3. **Storage and delivery.** A `StorageAdapter` writes bytes to the configured disk, and the `DeliveryAdapter` turns a stored file into the URL your views use.

Read [How Uploads Work](/guide/how-it-works) for the step-by-step pipeline.

## Next steps

- Check the [requirements](/guide/requirements).
- [Install the package](/guide/installation).
- Follow the [quickstart](/guide/quickstart) to wire an upload field into a form.
