---
title: MediaFlow Facade
description: Upload, move, rename, delete and optimize media from PHP with the MediaFlow facade in Laravel controllers, jobs and commands.
---

# MediaFlow Facade

`MediaFlow\Laravel\Facades\MediaFlow` gives you the same operations as the UI, for controllers, queued jobs and console commands. The facade alias `MediaFlow` is registered automatically.

```php
use MediaFlow\Laravel\Facades\MediaFlow;
```

## Methods

| Method | Returns | Description |
| --- | --- | --- |
| `upload(UploadedFile $file, array $options = [])` | `Media` | Validate, process and store a file. |
| `delete(Media\|int $media, bool $purgeStorage = true)` | `bool` | Soft-delete the record and, by default, remove the original and variant files. Fires `MediaDeleted`. |
| `move(Media\|int $media, ?int $targetFolderId)` | `Media` | Move to a folder, or to the root with `null`. |
| `rename(Media\|int $media, string $newDisplayName)` | `Media` | Change the display name. The stored file is not renamed. |
| `optimize(Media\|int $media, array $options = [])` | `array` | Re-encode or resize a stored image. See [Image Optimization](/api/optimization). |
| `createFolder(string $name, ?int $parentId = null, ?string $ownerId = null, ?string $tenantId = null)` | `MediaFolder` | Create a folder. |
| `testStorageConnection(?string $disk = null)` | `array` | Write, read and delete a probe file on a disk and report whether it worked. |
| `disk(?string $disk = null)` | `StorageAdapter` | The storage adapter for a disk. |
| `delivery()` | `DeliveryAdapter` | The URL resolver. |
| `authorize(Closure $callback)` | `void` | Register an extra access check. See [Access Control](/security/access-control#custom-authorization-callback). |

## Uploading

```php
$media = MediaFlow::upload($request->file('photo'), [
    'folder' => 'avatars',        // folder ID, name or path; missing folders are created
    'collection' => 'profiles',
    'visibility' => 'public',     // or 'private'
    'thumbnails' => true,
]);
```

All upload options:

| Option | Default | Description |
| --- | --- | --- |
| `disk` | `default_disk`, or `private_disk` for private files | Target disk. |
| `folder` / `folder_id` | root | Folder ID, name or path such as `blog/covers`. |
| `collection` | `default` | Collection name. |
| `visibility` | `public` | `public` or `private`. |
| `thumbnails` | `images.generate_thumbnails` | Generate preset variants. |
| `owner_id` | current user ID | Owner, used by [per-user scoping](/security/user-scoping). |
| `tenant_id` | `null` | Stored with the record; not enforced yet. |
| `optimize` | `images.auto_optimize` | Convert and downscale on upload. |
| `format`, `quality`, `max_width`, `max_height` | image settings | Per-upload image settings. |
| `max_kb`, `allowed_mimes` | config | Stricter [upload rules](/config/uploads#per-upload-rules) for this call. |

Validation failures throw `MediaFlow\Laravel\Exceptions\MediaUploadException` with a readable message.

```php
use MediaFlow\Laravel\Exceptions\MediaUploadException;

try {
    $media = MediaFlow::upload($file, ['folder' => 'invoices', 'visibility' => 'private']);
} catch (MediaUploadException $e) {
    return back()->withErrors(['file' => $e->getMessage()]);
}
```

## Folders, moving and renaming

```php
$folder = MediaFlow::createFolder('2026 Campaigns');
$child  = MediaFlow::createFolder('Spring', $folder->id);

MediaFlow::move($media, $child->id);
MediaFlow::rename($media, 'hero-banner.webp');
```

## Deleting

```php
MediaFlow::delete($media);          // record and files
MediaFlow::delete($media, false);   // record only, keep files in storage
```

## Storage checks from code

```php
$result = MediaFlow::testStorageConnection('s3');
// ['success' => true, 'message' => '...', 'details' => ['disk' => 's3', ...]]
```
