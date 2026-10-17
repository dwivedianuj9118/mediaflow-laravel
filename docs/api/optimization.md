---
title: Image Optimization
description: Convert, compress or resize images already stored in MediaFlow to WebP, JPEG or PNG with MediaFlow::optimize() or the optimize endpoint.
---

# Image Optimization

Uploads are optimized automatically (see [Image Processing](/config/images)). To convert or compress an image that is already stored, use `MediaFlow::optimize()` or the **Optimize** action in the media manager.

```php
use MediaFlow\Laravel\Facades\MediaFlow;

$result = MediaFlow::optimize($media, [
    'format'  => 'webp',   // webp | jpeg (or jpg) | png
    'quality' => 80,
    'width'   => 1600,     // optional max width, aspect ratio kept
    'height'  => 1600,     // optional max height
    'action'  => 'copy',   // 'replace' (default) or 'copy'
]);

$result['savings_percent'];   // e.g. 64.2
$result['size_before'];       // bytes
$result['size_after'];        // bytes
$result['width'];
$result['height'];
$result['format'];            // 'webp'
$result['mime'];              // 'image/webp'
$result['media'];             // the updated Media, or the new copy
```

| Option | Default | Notes |
| --- | --- | --- |
| `format` | `webp` | `webp`, `jpeg`/`jpg` or `png`. |
| `quality` | image default | 30–100 through the HTTP endpoint. |
| `width`, `height` | none | Maximum dimensions; the image is only scaled down. |
| `action` | `replace` | `replace` overwrites the file and updates the record. `copy` stores a new file and creates a new `Media` record in the same folder. |

## Limits

- Only raster images can be optimized. SVGs and non-images throw `InvalidArgumentException`.
- Converting to JPEG fills transparent areas with white.
- If writing the result to storage fails, a `RuntimeException` is thrown and the original is left untouched.

## HTTP endpoint

```http
POST /mediaflow/media/{id}/optimize
Content-Type: application/json

{ "format": "webp", "quality": 80, "max_width": 1600, "action": "replace" }
```

`width`/`height` (or `max_width`/`max_height`) accept 20–8000. The response contains `metrics` (the values above) and the updated `media`.
