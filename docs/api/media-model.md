---
title: Media Model
description: URL helpers, type checks, query scopes and relationships on MediaFlow's Media, MediaFolder and MediaVariant Eloquent models.
---

# Media Model

`MediaFlow\Laravel\Models\Media` is a regular Eloquent model with soft deletes.

## Relating it to your models

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MediaFlow\Laravel\Models\Media as MediaFlowMedia;

class Post extends Model
{
    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaFlowMedia::class, 'cover_media_id');
    }
}
```

For several files, store the IDs in a JSON column or a pivot table and query `MediaFlowMedia::whereIn('id', $ids)`.

## URL helpers

| Method | Returns |
| --- | --- |
| `url()` | The delivery URL: a disk URL, a CDN URL or a signed URL, depending on [delivery settings](/storage/delivery) and visibility. |
| `thumbnailUrl(string $preset = 'thumb')` | URL of a preset variant, or `url()` if that variant does not exist. |
| `temporaryUrl(int $minutes = 15)` | An expiring URL: a native pre-signed URL on S3, or the signed delivery route on local disks. |

```blade
<img src="{{ $post->cover->url() }}" alt="{{ $post->title }}">
<img src="{{ $post->cover->thumbnailUrl('medium') }}" alt="" loading="lazy">
<a href="{{ $post->cover->temporaryUrl(10) }}">Download</a>
```

## Other helpers

| Method | Returns |
| --- | --- |
| `humanSize()` | Size such as `1.24 MB`. |
| `isReady()` | `true` when status is `ready`. |
| `isImage()`, `isSvg()`, `isVideo()`, `isAudio()`, `isPdf()` | Type checks based on the detected MIME type. |

## Query scopes

```php
use MediaFlow\Laravel\Models\Media;

Media::ready()->inFolder($folderId)->inCollection('products')->search('banner')->latest()->get();
```

| Scope | Filters by |
| --- | --- |
| `ready()` | Status `ready`. |
| `inFolder(?int $folderId)` | Folder, or the root with `null`. |
| `inCollection(?string $collection)` | Collection, ignored when `null`. |
| `search(?string $term)` | File name, extension or MIME type; ignored when empty. |

## Relationships

| Model | Relationship |
| --- | --- |
| `Media` | `folder()` belongs to `MediaFolder`; `variants()` has many `MediaVariant`. |
| `MediaFolder` | `parent()`, `children()`, `media()`. Also `breadcrumbs()`, `allDescendantIds()` and `canMoveTo($parentId)`. |
| `MediaVariant` | `media()`; `url()` and `isReady()`. |

## Attributes

`uuid`, `folder_id`, `owner_id`, `tenant_id`, `collection`, `original_name`, `disk`, `storage_key`, `mime_type`, `extension`, `size_bytes`, `checksum` (SHA-256), `width`, `height`, `visibility`, `status` (`storing`, `ready`, `failed`), `error_message`, `metadata` (array), `uploaded_by`, timestamps and `deleted_at`.
