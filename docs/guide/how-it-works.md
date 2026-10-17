---
title: How Uploads Work
description: The five stages every MediaFlow upload goes through, from validation and temporary staging to image variants, storage and delivery URLs.
---

# How Uploads Work

Every upload, whether it comes from a Blade component, the REST API or `MediaFlow::upload()`, runs through the same pipeline inside a single request.

<div class="diagram">

![MediaFlow five-stage upload lifecycle](/images/mediaflow_upload_lifecycle.svg)

</div>

| Stage | What happens |
| --- | --- |
| 1. Receive | The browser sends the file to `POST /mediaflow/upload` with the CSRF token. The access-control middleware runs first. |
| 2. Validate | The real MIME type is read from the file contents, then size, allowed types, blocked extensions, SVG content and image megapixels are checked. The stored extension is derived from the detected type. |
| 3. Optimize | JPEG, PNG and WebP images have EXIF/GPS metadata removed and, when auto-optimize is on, are downscaled to the max dimensions and converted to the default format (WebP unless you change it). |
| 4. Catalog and store | A UUID storage key (`media/YYYY/MM/uuid.ext`) is generated, the folder is resolved, a `Media` row is created with status `storing` and the file is written to the target disk. |
| 5. Variants and delivery | One variant per thumbnail preset is generated and stored, the status becomes `ready`, `MediaReady` fires and the response contains the delivery URLs. |

If validation fails, nothing is stored and the reason is returned to the browser. If storing or processing fails later, files that were already written are removed, the status becomes `failed` with an error message, and `MediaFailed` fires. The error is logged and the browser gets a generic message.

::: info Synchronous processing
Variants are generated during the upload request. `images.queue_processing` is reserved for a future queued mode and has no effect yet.
:::

## Checking status

`GET /mediaflow/status/{id}` returns `status`, `is_ready`, `error_message` and `url` for a media item. Since processing is synchronous, the upload response already reflects the final status, so you only need this endpoint if you call the pipeline from elsewhere.

## The pipeline visualizer

The `/mediaflow/pipeline` page and the `pipeline` option on the components show an animated view of these stages while a file uploads. It is a visual aid only. It is enabled by `ui.show_pipeline` (on by default) and can be switched per component; see [Input Field](/components/input#options).

## Temporary files

Staged files are recorded in the `mediaflow_upload_sessions` table. Schedule `php artisan mediaflow:clean-temp` to remove expired sessions; see [Artisan Commands](/reference/artisan).
