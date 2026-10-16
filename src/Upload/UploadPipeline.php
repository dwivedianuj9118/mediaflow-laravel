<?php

namespace MediaFlow\Laravel\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MediaFlow\Laravel\Contracts\ImageProcessor;
use MediaFlow\Laravel\Events\MediaFailed;
use MediaFlow\Laravel\Events\MediaReady;
use MediaFlow\Laravel\Exceptions\MediaUploadException;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaFolder;
use MediaFlow\Laravel\Models\MediaVariant;
use MediaFlow\Laravel\Storage\StorageAdapterRegistry;

class UploadPipeline
{
    protected ValidationService $validator;
    protected StorageAdapterRegistry $storageRegistry;
    protected ImageProcessor $imageProcessor;
    protected TemporaryUploadManager $tempManager;

    public function __construct(
        ValidationService $validator,
        StorageAdapterRegistry $storageRegistry,
        ImageProcessor $imageProcessor,
        TemporaryUploadManager $tempManager
    ) {
        $this->validator = $validator;
        $this->storageRegistry = $storageRegistry;
        $this->imageProcessor = $imageProcessor;
        $this->tempManager = $tempManager;
    }

    /**
     * Execute full upload pipeline for an uploaded file.
     *
     * @throws MediaUploadException
     */
    public function process(UploadedFile $file, array $options = []): Media
    {
        // 1. Stage: Validating
        $validationResult = $this->validator->validateFile($file, $options);
        if (!$validationResult['valid']) {
            throw new MediaUploadException($validationResult['error'] ?? 'Validation failed.');
        }

        $visibility = ($options['visibility'] ?? 'public') === 'private' ? 'private' : 'public';
        $disk = $options['disk']
            ?? ($visibility === 'private' ? config('mediaflow.private_disk') : null)
            ?? config('mediaflow.default_disk', 'public');
        $collection = $options['collection'] ?? 'default';
        $ownerId = $options['owner_id'] ?? (auth()->check() ? (string) auth()->id() : null);
        $tenantId = $options['tenant_id'] ?? null;

        // Resolve folder ID
        $folderOption = $options['folder_id'] ?? $options['folder'] ?? null;
        $folderId = $this->resolveFolderId($folderOption, $ownerId, $tenantId);

        // Safe storage key generation
        $uuid = (string) Str::uuid();
        // Extension comes from the detected MIME type, never blindly from the client.
        $ext = $this->validator->safeExtension($file);
        $datePrefix = date('Y/m');
        $storageKey = "media/{$datePrefix}/{$uuid}.{$ext}";

        $adapter = $this->storageRegistry->get($disk);
        $writtenKeys = [];

        // Remove EXIF/GPS metadata before anything is stored (privacy).
        $realPath = $file->getRealPath();
        $strippedPath = $this->stripMetadataIfNeeded($realPath, $file->getMimeType() ?: '');
        $sourcePath = $strippedPath ?? $realPath;

        // Auto-optimize and convert images (e.g. to WebP) if configured
        $rawMime = $file->getMimeType() ?: 'application/octet-stream';
        $isProcessableImage = in_array($rawMime, ['image/jpeg', 'image/png', 'image/webp'], true)
            && $this->imageProcessor->isSupported();

        $settingsManager = app(\MediaFlow\Laravel\Support\MediaFlowSettingsManager::class);
        $imageSettings = $settingsManager->get('images', config('mediaflow.images', []));

        $autoOptimize = $options['optimize'] ?? $imageSettings['auto_optimize'] ?? config('mediaflow.images.auto_optimize', true);
        $defaultFormat = strtolower($options['format'] ?? $imageSettings['default_format'] ?? config('mediaflow.images.default_format', 'webp'));
        $quality = (int) ($options['quality'] ?? $imageSettings['default_quality'] ?? 82);
        $maxWidth = (int) ($options['max_width'] ?? $imageSettings['max_width'] ?? 2560);
        $maxHeight = (int) ($options['max_height'] ?? $imageSettings['max_height'] ?? 2560);

        $optimizedPath = null;
        $optimizationMeta = [];

        if ($isProcessableImage && $autoOptimize) {
            if ($defaultFormat === 'webp' && function_exists('imagewebp')) {
                $targetFormat = 'webp';
            } elseif (in_array($defaultFormat, ['jpeg', 'jpg'], true)) {
                $targetFormat = 'jpeg';
            } elseif ($defaultFormat === 'png') {
                $targetFormat = 'png';
            } elseif ($defaultFormat === 'keep') {
                $targetFormat = in_array($ext, ['jpeg', 'jpg'], true) ? 'jpeg' : ($ext === 'png' ? 'png' : 'webp');
            } else {
                $targetFormat = function_exists('imagewebp') ? 'webp' : (in_array($ext, ['jpeg', 'jpg'], true) ? 'jpeg' : 'png');
            }

            $optTemp = sys_get_temp_dir() . '/mf_opt_' . uniqid() . '.' . ($targetFormat === 'jpeg' ? 'jpg' : $targetFormat);
            $optResult = $this->imageProcessor->optimizeAndConvert($sourcePath, $optTemp, [
                'format' => $targetFormat,
                'quality' => $quality,
                'width' => $maxWidth,
                'height' => $maxHeight,
            ]);

            if ($optResult && file_exists($optTemp)) {
                $optimizedPath = $optTemp;
                $sourcePath = $optTemp;
                $ext = $targetFormat === 'jpeg' ? 'jpg' : $targetFormat;
                $rawMime = $optResult['mime'];
                $storageKey = "media/{$datePrefix}/{$uuid}.{$ext}";
                $optimizationMeta = [
                    'optimized' => true,
                    'original_size_bytes' => $optResult['size_before'],
                    'optimized_size_bytes' => $optResult['size_after'],
                    'savings_percent' => $optResult['savings_percent'],
                    'format' => $targetFormat,
                ];
            }
        }

        // 2. Stage: Creating initial record
        $media = new Media([
            'uuid' => $uuid,
            'folder_id' => $folderId,
            'owner_id' => $ownerId,
            'tenant_id' => $tenantId,
            'collection' => $collection,
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'storage_key' => $storageKey,
            'mime_type' => $rawMime,
            'extension' => $ext,
            'size_bytes' => (int) filesize($sourcePath),
            'checksum' => hash_file('sha256', $sourcePath),
            'visibility' => $visibility,
            'status' => 'storing',
            'uploaded_by' => auth()->check() ? (string) auth()->id() : null,
            'metadata' => array_merge([
                'client_original_name' => $file->getClientOriginalName(),
                'client_mime' => $file->getClientMimeType(),
                'exif_stripped' => $strippedPath !== null,
            ], $optimizationMeta),
        ]);

        try {
            // Check dimensions if image
            if ($media->isImage() && $this->imageProcessor->isSupported()) {
                $dims = $this->imageProcessor->extractDimensions($sourcePath);
                if ($dims) {
                    $media->width = $dims['width'];
                    $media->height = $dims['height'];
                }
            }

            // 3. Stage: Store original file
            $stream = fopen($sourcePath, 'r');
            $writeSuccess = $adapter->writeStream($storageKey, $stream, [
                'visibility' => $visibility,
                'mimetype' => $media->mime_type,
            ]);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if (!$writeSuccess) {
                throw new MediaUploadException("Failed to write media object to disk [{$disk}].");
            }

            $writtenKeys[] = $storageKey;
            $media->save();

            // 4. Stage: Generate variants (thumbnails) if image
            $generateThumbnails = $options['thumbnails'] ?? config('mediaflow.images.generate_thumbnails', true);
            if ($media->isImage() && !$media->isSvg() && $generateThumbnails && $this->imageProcessor->isSupported()) {
                $this->generateVariants($media, $sourcePath, $adapter, $writtenKeys);
            }

            // 5. Stage: Mark ready
            $media->update(['status' => 'ready']);
            event(new MediaReady($media));

            return $media;
        } catch (\Throwable $e) {
            // Clean up any partially written files to prevent orphaned objects
            foreach ($writtenKeys as $key) {
                try {
                    $adapter->delete($key);
                } catch (\Throwable $ignored) {
                }
            }

            if ($media->exists) {
                $media->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            event(new MediaFailed($media, $e->getMessage()));

            if ($e instanceof MediaUploadException) {
                throw $e;
            }

            throw new MediaUploadException($e->getMessage(), (int) $e->getCode(), $e);
        } finally {
            if ($optimizedPath !== null && file_exists($optimizedPath)) {
                @unlink($optimizedPath);
            }
            if ($strippedPath !== null && file_exists($strippedPath)) {
                @unlink($strippedPath);
            }
        }
    }

    /**
     * Re-encode JPEG/PNG/WebP images that carry EXIF/XMP metadata so GPS location and
     * camera details are not published. Returns the path of a cleaned temporary copy,
     * or null when nothing needed stripping (or stripping is disabled/unsupported).
     */
    protected function stripMetadataIfNeeded(string $path, string $mime): ?string
    {
        if (!config('mediaflow.images.strip_exif', true) || !$this->imageProcessor->isSupported()) {
            return null;
        }

        try {
            $setting = app(\MediaFlow\Laravel\Support\MediaFlowSettingsManager::class)->getPersisted('images.strip_exif');
            if ($setting !== null && !filter_var($setting, FILTER_VALIDATE_BOOLEAN)) {
                return null;
            }
        } catch (\Throwable $e) {
            // settings table unavailable: fall back to config
        }

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $head = (string) @file_get_contents($path, false, null, 0, 262144);
        $hasMetadata = str_contains($head, "Exif\x00\x00")
            || str_contains($head, 'eXIf')
            || str_contains($head, 'EXIF')
            || str_contains($head, 'http://ns.adobe.com/xap/1.0/');

        if (!$hasMetadata) {
            return null;
        }

        $target = tempnam(sys_get_temp_dir(), 'mf_exif_');
        if ($target === false) {
            return null;
        }

        if ($this->imageProcessor->stripExif($path, $target) && filesize($target) > 0) {
            return $target;
        }

        @unlink($target);

        return null;
    }

    /**
     * Generate configured image variant presets.
     */
    protected function generateVariants(Media $media, string $sourcePath, $adapter, array &$writtenKeys): void
    {
        $presets = config('mediaflow.images.presets', []);
        $tempDir = sys_get_temp_dir() . '/mediaflow_variants_' . uniqid();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        foreach ($presets as $presetName => $presetConfig) {
            $format = $presetConfig['format'] ?? 'webp';
            $variantFileName = "{$media->uuid}_{$presetName}.{$format}";
            $localVariantPath = "{$tempDir}/{$variantFileName}";

            $result = $this->imageProcessor->createPreset($sourcePath, $localVariantPath, $presetConfig);
            if (!$result || !file_exists($localVariantPath)) {
                continue;
            }

            $variantStorageKey = "media/" . date('Y/m') . "/variants/{$variantFileName}";
            $stream = fopen($localVariantPath, 'r');
            $stored = $adapter->writeStream($variantStorageKey, $stream, [
                'visibility' => $media->visibility,
                'mimetype' => $result['mime'],
            ]);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($stored) {
                $writtenKeys[] = $variantStorageKey;

                MediaVariant::updateOrCreate(
                    [
                        'media_id' => $media->id,
                        'preset' => $presetName,
                    ],
                    [
                        'disk' => $media->disk,
                        'storage_key' => $variantStorageKey,
                        'mime_type' => $result['mime'],
                        'size_bytes' => $result['size'],
                        'width' => $result['width'],
                        'height' => $result['height'],
                        'checksum' => hash_file('sha256', $localVariantPath),
                        'status' => 'ready',
                    ]
                );
            }

            @unlink($localVariantPath);
        }

        @rmdir($tempDir);
    }

    /**
     * Resolve folder ID from integer ID, slug, name, or path string like "blog/covers".
     */
    protected function resolveFolderId(int|string|null $folder, ?string $ownerId, ?string $tenantId): ?int
    {
        if (empty($folder) || $folder === 'null' || $folder === 'undefined') {
            return null;
        }

        if (is_numeric($folder)) {
            $folderId = (int) $folder;
            $folderModel = MediaFolder::find($folderId);
            if ($folderModel) {
                return $folderModel->id;
            }
        }

        $folderStr = (string) $folder;

        // Check if an existing folder directly matches the name or slug
        $existing = MediaFolder::where('name', $folderStr)
            ->orWhere('slug', Str::slug($folderStr))
            ->first();
        if ($existing) {
            return $existing->id;
        }

        // Parse path string e.g. "blog/covers"
        $segments = array_filter(explode('/', trim($folderStr, '/')));
        if (empty($segments)) {
            return null;
        }

        $parentId = null;
        foreach ($segments as $name) {
            $folderModel = MediaFolder::where('name', $name)
                ->where('parent_id', $parentId)
                ->first();

            if (!$folderModel) {
                $folderModel = MediaFolder::create([
                    'name' => $name,
                    'parent_id' => $parentId,
                    'owner_id' => $ownerId,
                    'tenant_id' => $tenantId,
                ]);
            }

            $parentId = $folderModel->id;
        }

        return $parentId;
    }
}
