---
title: Image Processing
description: Configure MediaFlow's automatic WebP conversion, downscaling, EXIF and GPS stripping and thumbnail presets for Laravel image uploads.
---

# Image Processing

MediaFlow processes JPEG, PNG and WebP uploads with the GD extension. Settings come from the [Settings page](/config/settings-page) when saved there, otherwise from `config/mediaflow.php`.

```php
'images' => [
    'driver' => 'gd',
    'default_format' => 'webp',   // webp | jpeg | png | keep
    'default_quality' => 82,      // 30–100
    'auto_optimize' => true,
    'max_width' => 2560,
    'max_height' => 2560,
    'strip_exif' => true,
    'generate_thumbnails' => true,
    'presets' => [ /* see below */ ],
],
```

## Automatic optimization

With `auto_optimize` on, every JPEG, PNG and WebP upload is:

1. scaled down proportionally to fit `max_width` × `max_height`, and
2. re-encoded in `default_format` at `default_quality`.

`keep` leaves the format unchanged and only resizes. If the server's GD build lacks WebP support, MediaFlow keeps JPEG and PNG in their own format.

| Format | Good for |
| --- | --- |
| WebP | Most web images. Small files, transparency support. The default. |
| JPEG | Photos where you need maximum compatibility. Transparent areas become white. |
| PNG | Screenshots, diagrams and images that must stay lossless. |

::: info AVIF
AVIF is not supported. The settings endpoint accepts `webp`, `jpeg`, `png` and `keep` only.
:::

A quality between 80 and 85 is a good balance for WebP. Lower values save more bytes with visible artefacts on detailed photos.

## EXIF and GPS stripping

With `strip_exif` on, JPEG, PNG and WebP files that contain EXIF or XMP metadata are re-encoded without it, removing GPS coordinates, camera details and similar personal data. JPEG orientation is applied first, so photos are not rotated. Install `ext-exif` for the orientation fix.

## Thumbnail presets

Each raster image gets one variant per preset. The defaults are:

```php
'presets' => [
    'thumb'  => ['width' => 200,  'height' => 200,  'fit' => 'crop',    'format' => 'webp', 'quality' => 80],
    'medium' => ['width' => 800,  'height' => 800,  'fit' => 'contain', 'format' => 'webp', 'quality' => 85],
    'large'  => ['width' => 1600, 'height' => 1600, 'fit' => 'contain', 'format' => 'webp', 'quality' => 85],
],
```

- `crop` fills the box exactly and trims the overflow.
- `contain` fits the image inside the box and keeps its aspect ratio.

Add your own, for example a social sharing image:

```php
'og' => ['width' => 1200, 'height' => 630, 'fit' => 'crop', 'format' => 'jpeg', 'quality' => 85],
```

New presets apply to new uploads. Use a preset by name:

```blade
<img src="{{ $media->thumbnailUrl('medium') }}" alt="">
```

To skip variants for one upload, pass `'thumbnails' => false` to `MediaFlow::upload()` or the upload endpoint. Set `generate_thumbnails` to `false` to turn them off everywhere.

## Per-upload overrides

`MediaFlow::upload()` accepts `optimize`, `format`, `quality`, `max_width` and `max_height` to override the defaults for a single file:

```php
MediaFlow::upload($file, [
    'format' => 'jpeg',
    'quality' => 90,
    'max_width' => 1920,
]);
```

## Reserved options

`images.queue_processing`, `images.queue_name` and `images.driver = imagick` are reserved and have no effect yet. Images are processed synchronously with GD during the upload request. To use another engine, bind your own `ImageProcessor`; see [Extending](/reference/extending).

To convert or compress an image that is already stored, see [Image Optimization](/api/optimization).
