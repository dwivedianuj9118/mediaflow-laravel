---
title: Database Tables
description: MediaFlow's database tables, how to change their prefix or names, and how to run it alongside Spatie MediaLibrary or an existing media model.
---

# Database Tables

MediaFlow creates five tables, all with the `mediaflow_` prefix by default:

| Table | Holds |
| --- | --- |
| `mediaflow_media` | One row per file: disk, storage key, MIME type, size, dimensions, visibility, status, owner and metadata. Uses soft deletes. |
| `mediaflow_folders` | The folder tree (`parent_id`, `slug`, `path`, `owner_id`). |
| `mediaflow_variants` | Generated sizes per media item and preset. Deleted with their media row. |
| `mediaflow_upload_sessions` | Temporary upload sessions, cleaned by `mediaflow:clean-temp`. |
| `mediaflow_settings` | Values saved from the Settings page. |

## Changing the prefix

Set the prefix in `.env` **before** you run the migrations:

```dotenv
# Creates mf_media, mf_folders, mf_variants, mf_upload_sessions, mf_settings
MEDIAFLOW_TABLE_PREFIX=mf_
```

Or override a single table:

```dotenv
MEDIAFLOW_TABLE_MEDIA=media_library
```

The models read the table names from config, so nothing else changes. Remember to use the new name in validation rules such as `Rule::exists('mf_media', 'id')`.

## Running next to an existing media system

If your app already has a `media` table or an `App\Models\Media` model, for example from Spatie MediaLibrary, MediaFlow does not touch it:

- Its models live in their own namespace, `MediaFlow\Laravel\Models`.
- Its tables are prefixed, so `mediaflow_media` never collides with `media`.

Alias the model where both are used:

```php
use App\Models\Media;                                  // your existing model
use MediaFlow\Laravel\Models\Media as MediaFlowMedia;  // MediaFlow
```

## Models

| Model | Table |
| --- | --- |
| `MediaFlow\Laravel\Models\Media` | `tables.media` |
| `MediaFlow\Laravel\Models\MediaFolder` | `tables.folders` |
| `MediaFlow\Laravel\Models\MediaVariant` | `tables.variants` |
| `MediaFlow\Laravel\Models\UploadSession` | `tables.upload_sessions` |
| `MediaFlow\Laravel\Models\MediaFlowSetting` | `tables.settings` |

See [Media Model](/api/media-model) for helpers and relationships.
