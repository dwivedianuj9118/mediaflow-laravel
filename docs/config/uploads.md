---
title: Upload Rules
description: How MediaFlow validates uploads on the server, including size limits, allowed MIME types, blocked extensions, SVG checks and image size limits.
---

# Upload Rules

The browser components filter files for convenience, but every rule below is enforced on the server, in this order. The first failing rule rejects the upload with a readable message.

| # | Rule | Configured by |
| --- | --- | --- |
| 1 | The file name must not be empty, longer than 255 characters, contain control characters, `..`, `/` or `\`. | Fixed |
| 2 | The file's own extension must not be in `blocked_extensions`. | `blocked_extensions` |
| 3 | The size must not exceed the limit. | Per-call `max_kb`, then the Settings page value, then `max_file_size_kb` |
| 4 | The MIME type **detected from the file contents** must be allowed. Wildcards such as `image/*` work. | Per-call `allowed_mimes`, then `allowed_mimes` |
| 5 | The extension derived from the detected type must not be blocked either. | `blocked_extensions` |
| 6 | SVGs must not contain scripts, event handlers, `<foreignObject>`, external `href`s, entities or encoded payloads. | `sanitize_svg` (on) |
| 7 | Raster images must not exceed the megapixel limit. | `max_image_megapixels` (50) |

## Stored extension

The stored file's extension is derived from the detected MIME type, never taken from the client name. The client's extension is kept only when it is a valid one for that type (for example `.jpeg` instead of `.jpg`). So `invoice.html` that is really plain text is stored as `.txt`.

## Allowed types

The default `allowed_mimes` list covers:

- **Images:** JPEG, PNG, WebP, GIF, SVG
- **Documents:** PDF, Word, Excel, PowerPoint, plain text, CSV
- **Video:** MP4, QuickTime, WebM, Ogg
- **Audio:** MP3, WAV, Ogg, M4A
- **Archives:** ZIP

Edit the list in `config/mediaflow.php` to narrow or widen it:

```php
'allowed_mimes' => [
    'image/*',
    'application/pdf',
],
```

## Blocked extensions

By default PHP variants (`php`, `phtml`, `phar` and others), shell and Windows executables, `js`, `mjs`, `py`, `pl`, `cgi`, `html`, `htm`, `xhtml`, `shtml`, `xml`, `svgz`, `htaccess`, `jsp`, `asp` and `aspx` are refused, whatever their content.

## SVG files

SVG can carry scripts, so `sanitize_svg` rejects unsafe files rather than trying to clean them. If you do not need SVG at all, remove `image/svg+xml` from `allowed_mimes`. When SVGs are served through the signed delivery route, they are sent as downloads, not rendered inline.

## Per-upload rules

Pass `max_kb` or `allowed_mimes` to `MediaFlow::upload()` to tighten the rules for one call:

```php
MediaFlow::upload($request->file('avatar'), [
    'folder' => 'avatars',
    'max_kb' => 2048,
    'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
]);
```

## PHP and server limits

The server rejects oversized requests before MediaFlow sees them. Set `upload_max_filesize`, `post_max_size` and your web server's body limit to at least `MEDIAFLOW_MAX_FILE_SIZE_KB`. An empty upload or a `413` response usually means one of these is too low.
