<?php

namespace MediaFlow\Laravel\Delivery;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use MediaFlow\Laravel\Contracts\DeliveryAdapter;
use MediaFlow\Laravel\Contracts\StorageAdapter;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaVariant;
use MediaFlow\Laravel\Storage\StorageAdapterRegistry;

class DeliveryUrlResolver implements DeliveryAdapter
{
    protected StorageAdapterRegistry $storageRegistry;

    public function __construct(StorageAdapterRegistry $storageRegistry)
    {
        $this->storageRegistry = $storageRegistry;
    }

    public function url(Media $media): string
    {
        // 1. If media is private (or the 'signed' delivery driver is active), generate an expiring signed URL
        if ($media->visibility === 'private' || config('mediaflow.delivery.driver') === 'signed') {
            return $this->temporaryUrl($media, (int) config('mediaflow.delivery.signed_url_expiration_minutes', 15));
        }

        // 2. Custom CDN / Domain URL
        $baseUrl = config('mediaflow.delivery.base_url');
        $driver = config('mediaflow.delivery.driver', 'disk');

        if ($driver === 'cdn' && !empty($baseUrl)) {
            return rtrim($baseUrl, '/') . '/' . ltrim($media->storage_key, '/');
        }

        // 3. Fallback to storage adapter public URL
        $storage = $this->storageRegistry->get($media->disk);
        return $storage->url($media->storage_key);
    }

    public function variantUrl(MediaVariant $variant): string
    {
        // If parent media is private, respect privacy
        $parent = $variant->relationLoaded('media')
            ? $variant->media
            : $variant->media()->withoutGlobalScopes()->first();

        if (($parent && $parent->visibility === 'private') || config('mediaflow.delivery.driver') === 'signed') {
            $storage = $this->storageRegistry->get($variant->disk);
            $expiry = now()->addMinutes((int) config('mediaflow.delivery.signed_url_expiration_minutes', 15));

            try {
                return $storage->temporaryUrl($variant->storage_key, $expiry);
            } catch (\Throwable $e) {
                // Local disks have no native temporary URLs: fall back to the signed stream route.
            }

            if (Route::has('mediaflow.delivery.stream')) {
                return URL::temporarySignedRoute(
                    'mediaflow.delivery.stream',
                    $expiry,
                    ['media' => $variant->media_id, 'variant' => $variant->preset]
                );
            }

            return $storage->url($variant->storage_key);
        }

        $baseUrl = config('mediaflow.delivery.base_url');
        $driver = config('mediaflow.delivery.driver', 'disk');

        if ($driver === 'cdn' && !empty($baseUrl)) {
            return rtrim($baseUrl, '/') . '/' . ltrim($variant->storage_key, '/');
        }

        $storage = $this->storageRegistry->get($variant->disk);
        return $storage->url($variant->storage_key);
    }

    public function temporaryUrl(Media $media, int $minutes = 15): string
    {
        $storage = $this->storageRegistry->get($media->disk);
        $expiry = now()->addMinutes($minutes);

        // First attempt direct disk temporary URL (e.g. AWS S3 signed URL)
        try {
            return $storage->temporaryUrl($media->storage_key, $expiry);
        } catch (\Throwable $e) {
            // Local disk or driver without native temporaryUrl falls back to application signed route
        }

        if (Route::has('mediaflow.delivery.stream')) {
            return URL::temporarySignedRoute(
                'mediaflow.delivery.stream',
                $expiry,
                ['media' => $media->id]
            );
        }

        return $storage->url($media->storage_key);
    }
}
