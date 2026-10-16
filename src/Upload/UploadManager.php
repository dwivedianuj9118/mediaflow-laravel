<?php

namespace MediaFlow\Laravel\Upload;

use Illuminate\Http\UploadedFile;
use MediaFlow\Laravel\Contracts\DeliveryAdapter;
use MediaFlow\Laravel\Contracts\StorageAdapter;
use MediaFlow\Laravel\Events\MediaDeleted;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaFolder;
use MediaFlow\Laravel\Storage\StorageAdapterRegistry;

class UploadManager
{
    protected UploadPipeline $pipeline;
    protected StorageAdapterRegistry $storageRegistry;
    protected DeliveryAdapter $deliveryResolver;

    public function __construct(
        UploadPipeline $pipeline,
        StorageAdapterRegistry $storageRegistry,
        DeliveryAdapter $deliveryResolver
    ) {
        $this->pipeline = $pipeline;
        $this->storageRegistry = $storageRegistry;
        $this->deliveryResolver = $deliveryResolver;
    }

    protected static ?\Closure $authCallback = null;

    /**
     * Register a custom authorization callback for MediaFlow access.
     */
    public function authorizeUsing(?\Closure $callback): self
    {
        static::$authCallback = $callback;
        return $this;
    }

    /**
     * Check if a custom authorization callback is registered.
     */
    public function hasAuthorizationCallback(): bool
    {
        return static::$authCallback !== null;
    }

    /**
     * Determine whether the given user/request is authorized.
     */
    public function isAuthorized($request, $user = null): bool
    {
        if (static::$authCallback === null) {
            return true;
        }

        return (bool) call_user_func(static::$authCallback, $user, $request);
    }

    /**
     * Upload and process a media file.
     *
     * @param array{disk?: string, folder?: int|string, collection?: string, visibility?: string, thumbnails?: bool} $options
     */
    public function upload(UploadedFile $file, array $options = []): Media
    {
        return $this->pipeline->process($file, $options);
    }

    /**
     * Delete media record and associated storage objects.
     */
    public function delete(Media|int $media, bool $purgeStorage = true): bool
    {
        if (is_int($media)) {
            $media = Media::findOrFail($media);
        }

        if ($purgeStorage) {
            $adapter = $this->storageRegistry->get($media->disk);
            $adapter->delete($media->storage_key);

            // Delete variants
            foreach ($media->variants as $variant) {
                $variantAdapter = $this->storageRegistry->get($variant->disk);
                $variantAdapter->delete($variant->storage_key);
            }
        }

        $deleted = $media->delete();
        if ($deleted) {
            event(new MediaDeleted($media));
        }

        return (bool) $deleted;
    }

    /**
     * Move media item to a logical folder.
     */
    public function move(Media|int $media, ?int $targetFolderId): Media
    {
        if (is_int($media)) {
            $media = Media::findOrFail($media);
        }

        if ($targetFolderId !== null) {
            MediaFolder::findOrFail($targetFolderId);
        }

        $media->folder_id = $targetFolderId;
        $media->save();

        return $media;
    }

    /**
     * Rename file display name without altering storage key.
     */
    public function rename(Media|int $media, string $newDisplayName): Media
    {
        if (is_int($media)) {
            $media = Media::findOrFail($media);
        }

        $media->original_name = trim($newDisplayName);
        $media->save();

        return $media;
    }

    /**
     * Create a logical media folder.
     */
    public function createFolder(string $name, ?int $parentId = null, ?string $ownerId = null, ?string $tenantId = null): MediaFolder
    {
        if ($parentId !== null) {
            MediaFolder::findOrFail($parentId);
        }

        return MediaFolder::create([
            'name' => trim($name),
            'parent_id' => $parentId,
            'owner_id' => $ownerId ?? (auth()->check() ? (string) auth()->id() : null),
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Test connection to a storage disk.
     */
    public function testStorageConnection(?string $disk = null): array
    {
        $adapter = $this->storageRegistry->get($disk);
        return $adapter->testConnection();
    }

    /**
     * Get delivery URL resolver.
     */
    public function delivery(): DeliveryAdapter
    {
        return $this->deliveryResolver;
    }

    /**
     * Get storage adapter for a disk.
     */
    public function disk(?string $disk = null): StorageAdapter
    {
        return $this->storageRegistry->get($disk);
    }

    /**
     * Optimize, convert or resize an existing media item.
     *
     * @param array{quality?: int, format?: string, width?: int, height?: int, action?: string} $options
     */
    public function optimize(Media|int $media, array $options = []): array
    {
        if (is_int($media)) {
            $media = Media::findOrFail($media);
        }

        if (!$media->isImage() || $media->isSvg()) {
            throw new \InvalidArgumentException('Only images can be optimized or converted.');
        }

        $adapter = $this->storageRegistry->get($media->disk);
        $tempSource = tempnam(sys_get_temp_dir(), 'mf_src_');
        $tempDest = tempnam(sys_get_temp_dir(), 'mf_dst_');

        try {
            $contents = $adapter->read($media->storage_key);
            file_put_contents($tempSource, $contents);

            /** @var \MediaFlow\Laravel\Contracts\ImageProcessor $imageProcessor */
            $imageProcessor = app(\MediaFlow\Laravel\Contracts\ImageProcessor::class);
            $metrics = $imageProcessor->optimizeAndConvert($tempSource, $tempDest, $options);

            if (!$metrics) {
                throw new \RuntimeException('Image optimization failed.');
            }

            $action = $options['action'] ?? 'replace';

            if ($action === 'replace') {
                $newExt = $metrics['format'] === 'jpeg' ? 'jpg' : $metrics['format'];
                $baseKey = preg_replace('/\.[^.]+$/', '', $media->storage_key);
                $newStorageKey = "{$baseKey}.{$newExt}";

                $stream = fopen($tempDest, 'r');
                $written = $adapter->writeStream($newStorageKey, $stream, [
                    'visibility' => $media->visibility,
                    'mimetype' => $metrics['mime'],
                ]);
                if (is_resource($stream)) fclose($stream);

                if (!$written) {
                    throw new \RuntimeException("Failed to write optimized image to disk [{$media->disk}].");
                }

                if ($newStorageKey !== $media->storage_key) {
                    $adapter->delete($media->storage_key);
                }

                $media->update([
                    'original_name' => pathinfo($media->original_name, PATHINFO_FILENAME) . '.' . $newExt,
                    'storage_key' => $newStorageKey,
                    'extension' => $newExt,
                    'mime_type' => $metrics['mime'],
                    'size_bytes' => $metrics['size_after'],
                    'width' => $metrics['width'],
                    'height' => $metrics['height'],
                    'checksum' => hash_file('sha256', $tempDest),
                ]);

                $metrics['media'] = $media->fresh();
            } elseif ($action === 'copy') {
                $newExt = $metrics['format'] === 'jpeg' ? 'jpg' : $metrics['format'];
                $uuid = (string) \Illuminate\Support\Str::uuid();
                $datePrefix = date('Y/m');
                $newKey = "media/{$datePrefix}/{$uuid}.{$newExt}";

                $stream = fopen($tempDest, 'r');
                $written = $adapter->writeStream($newKey, $stream, [
                    'visibility' => $media->visibility,
                    'mimetype' => $metrics['mime'],
                ]);
                if (is_resource($stream)) fclose($stream);

                if (!$written) {
                    throw new \RuntimeException("Failed to write optimized image to disk [{$media->disk}].");
                }

                $newMedia = Media::create([
                    'uuid' => $uuid,
                    'folder_id' => $media->folder_id,
                    'owner_id' => $media->owner_id,
                    'tenant_id' => $media->tenant_id,
                    'collection' => $media->collection,
                    'original_name' => pathinfo($media->original_name, PATHINFO_FILENAME) . '-optimized.' . $newExt,
                    'disk' => $media->disk,
                    'storage_key' => $newKey,
                    'mime_type' => $metrics['mime'],
                    'extension' => $newExt,
                    'size_bytes' => $metrics['size_after'],
                    'width' => $metrics['width'],
                    'height' => $metrics['height'],
                    'checksum' => hash_file('sha256', $tempDest),
                    'visibility' => $media->visibility,
                    'status' => 'ready',
                ]);

                $metrics['media'] = $newMedia;
            }

            return $metrics;
        } finally {
            @unlink($tempSource);
            @unlink($tempDest);
        }
    }
}
