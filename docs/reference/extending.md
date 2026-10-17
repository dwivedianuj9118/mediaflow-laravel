---
title: Extending
description: Replace MediaFlow's image processor, add custom storage adapters for any disk, and use the delivery resolver directly in Laravel.
---

# Extending

MediaFlow is built on three contracts in `MediaFlow\Laravel\Contracts`. You can swap any of them from a service provider.

| Contract | Default | Responsible for |
| --- | --- | --- |
| `StorageAdapter` | `S3StorageAdapter` for `s3` disks, `LocalStorageAdapter` for every other Flysystem disk | Reading, writing, deleting and URLs for one disk. |
| `ImageProcessor` | `GdImageProcessor` | Dimensions, EXIF stripping, presets and optimization. |
| `DeliveryAdapter` | `DeliveryUrlResolver` | Turning media and variants into URLs. |

## Custom image processor

Implement `ImageProcessor` (`isSupported`, `extractDimensions`, `stripExif`, `createPreset`, `optimizeAndConvert`) and bind it:

```php
use MediaFlow\Laravel\Contracts\ImageProcessor;

public function register(): void
{
    $this->app->singleton(ImageProcessor::class, \App\Media\ImagickProcessor::class);
}
```

## Custom storage adapter

Implement `StorageAdapter` and register it for a disk name:

```php
use MediaFlow\Laravel\Storage\StorageAdapterRegistry;

public function boot(StorageAdapterRegistry $registry): void
{
    $registry->registerAdapter('gcs', new \App\Media\GcsStorageAdapter('gcs'));
}
```

The contract covers `write`, `writeStream`, `read`, `readStream`, `delete`, `exists`, `url`, `temporaryUrl`, `size`, `mimeType`, `copy`, `move` and `testConnection`.

## Custom delivery

Bind your own `DeliveryAdapter` (`url`, `variantUrl`, `temporaryUrl`) to change how URLs are built, for example to add image-CDN transformation parameters:

```php
use MediaFlow\Laravel\Contracts\DeliveryAdapter;

$this->app->singleton(DeliveryAdapter::class, \App\Media\ImgixDelivery::class);
```

## Useful accessors

```php
MediaFlow::disk('s3');          // StorageAdapter for a disk
MediaFlow::delivery();          // DeliveryAdapter
MediaFlow::testStorageConnection('s3');
```

## Customising the views

Publish the views to change markup:

```bash
php artisan vendor:publish --tag=mediaflow-views
```

They are copied to `resources/views/vendor/mediaflow`. Compare them with the package after updates.
