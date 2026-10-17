---
title: REST API
description: Every MediaFlow HTTP endpoint for uploads, media, folders, settings, diagnostics and signed delivery, with parameters and responses.
---

# REST API

All endpoints use the `MEDIAFLOW_ROUTE_PREFIX` (default `mediaflow`), the `web` middleware (session and CSRF) and [access control](/security/access-control). From JavaScript, send `X-CSRF-TOKEN` and `Accept: application/json`. Route names start with `mediaflow.`, e.g. `route('mediaflow.upload')`.

```js
const headers = {
  Accept: 'application/json',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
}
```

## Uploads

| Method | Endpoint | Name | Parameters | Response |
| --- | --- | --- | --- | --- |
| `POST` | `/upload` | `upload` | `file` (required), `folder` or `folder_id` (ID, name or path like `blog/covers`), `collection`, `visibility` (`public`/`private`), `thumbnails` (bool) | `{ success, media }` |
| `GET` | `/status/{id}` | `status` | — | `status`, `is_ready`, `error_message`, `url` |
| `GET` | `/csrf-token` | `csrf-token` | — | A fresh CSRF token for long-lived pages. Public. |

The `media` object contains `id`, `uuid`, `name`, `original_name`, `extension`, `mime_type`, `size_bytes`, `human_size`, `url`, `thumbnail_url`, `status`, `is_image`, `is_video`, `is_audio`, `is_document`, `width`, `height` and `folder_id`.

Errors return `{ success: false, error }` with a `4xx` status.

```js
const body = new FormData()
body.append('file', fileInput.files[0])
body.append('folder', 'blog/covers')

const { media } = await fetch('/mediaflow/upload', { method: 'POST', headers, body }).then((r) => r.json())
```

## Media

| Method | Endpoint | Name | Parameters | Description |
| --- | --- | --- | --- | --- |
| `GET` | `/manager` | `manager` | `folder_id`, `search`, `type` (`image`, `video`, `document`), `collection`, `sort` (`created_at`, `original_name`, `size_bytes`), `order` (`asc`/`desc`), `page` | HTML page, or JSON with `folders`, `media`, `breadcrumbs` and `pagination` for JSON/AJAX requests. 24 items per page. |
| `GET` | `/media/{id}` | `media.show` | — | One media item with its variants. |
| `PATCH` | `/media/{id}` | `media.update` | `name`, `folder_id` | Rename (display name only) and/or move. |
| `DELETE` | `/media/{id}` | `media.destroy` | — | Soft-delete the record and remove the original and variant files. |
| `POST` | `/media/{id}/optimize` | `media.optimize` | `format` (`webp`, `jpeg`, `jpg`, `png`), `quality` (30–100), `width`/`max_width`, `height`/`max_height` (20–8000), `action` (`replace`/`copy`) | Re-encode or resize. Returns `metrics` and `media`. See [Image Optimization](/api/optimization). |
| `POST` | `/media/bulk` | `media.bulk` | `action` (`delete`/`move`), `ids[]` (max 500), `target_folder_id` | Bulk delete or move. |

## Folders

| Method | Endpoint | Name | Parameters | Description |
| --- | --- | --- | --- | --- |
| `GET` | `/folders` | `folders.index` | `parent_id` | Child folders with media and child counts. |
| `POST` | `/folders` | `folders.store` | `name`, `parent_id` | Create a folder. Names are unique per parent. |
| `PATCH` | `/folders/{id}` | `folders.update` | `name`, `parent_id` | Rename or move. Descendant paths are updated; moving into its own subtree is rejected. |
| `DELETE` | `/folders/{id}` | `folders.destroy` | — | Delete a folder. Its files and subfolders move up to the parent. |

## Settings

| Method | Endpoint | Name | Description |
| --- | --- | --- | --- |
| `GET` | `/settings` | `settings` | Settings page, or JSON settings (secrets masked) and the provider list. `/settings-page` is an alias. |
| `POST` | `/settings` | `settings.update` | Save settings. See [accepted keys](/config/settings-page#accepted-keys). Unknown keys are ignored; invalid values return `422`. |
| `POST` | `/settings/reset` | `settings.reset` | Delete all saved settings. |
| `POST` | `/settings/test-provider` | `settings.test-provider` | Test cloud credentials. See [Cloud Providers](/storage/cloud-providers#testing-a-connection). |
| `GET` | `/settings/providers` | `settings.providers` | Metadata for the ten provider presets. |

## Diagnostics

| Method | Endpoint | Name | Description |
| --- | --- | --- | --- |
| `GET` | `/diagnostics` | `diagnostics.view` | Diagnostics page. |
| `GET` | `/diagnostics/info` | `diagnostics.info` | PHP and Laravel versions, GD/Imagick/WebP support, S3 driver status and active storage config. |
| `POST` | `/diagnostics/test` | `diagnostics.test` | Write/read/delete probe on `disk`, which must be defined in `config/filesystems.php`. |

## Pages and delivery

| Method | Endpoint | Name | Description |
| --- | --- | --- | --- |
| `GET` | `/` and `/manager` | `manager` | Media manager page. |
| `GET` | `/pipeline` | `pipeline` | Upload pipeline visualizer. |
| `GET` | `/docs` and `/documentation` | `docs`, `docs.alias` | In-app documentation. Public. |
| `GET` | `/delivery/{media}` | `delivery.stream` | Signed streaming of private files. Public; requires a valid `signature`. Accepts `variant`. |

## Assets

| Endpoint | Name | Description |
| --- | --- | --- |
| `/vendor/mediaflow/css/mediaflow.css`, `/vendor/mediaflow/js/mediaflow.js`, `/vendor/mediaflow/img/{file}` | `mediaflow.asset.*` | Fallback asset routes, used until you publish the assets. Not prefixed. |
| `/mediaflow/assets/css`, `/assets/js`, `/assets/img/{file}` | `mediaflow.assets.*` | Prefixed aliases of the same files. |
