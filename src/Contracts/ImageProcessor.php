<?php

namespace MediaFlow\Laravel\Contracts;

interface ImageProcessor
{
    /**
     * Check if the processor engine is supported and available in the current runtime.
     */
    public function isSupported(): bool;

    /**
     * Get image dimensions.
     *
     * @return array{width: int, height: int}|null
     */
    public function extractDimensions(string $realPath): ?array;

    /**
     * Strip EXIF/location metadata for privacy.
     */
    public function stripExif(string $sourcePath, string $destinationPath): bool;

    /**
     * Create a processed image variant preset (crop, resize, format change).
     *
     * @param array{width: int, height: int, fit?: string, format?: string, quality?: int} $presetConfig
     * @return array{width: int, height: int, size: int, mime: string}|null
     */
    public function createPreset(string $sourcePath, string $destinationPath, array $presetConfig): ?array;

    /**
     * Optimize and optionally convert or resize an image.
     *
     * @param array{quality?: int, format?: string, width?: int, height?: int, fit?: string, strip_exif?: bool} $options
     * @return array{size_before: int, size_after: int, width: int, height: int, mime: string, format: string, savings_percent: float}|null
     */
    public function optimizeAndConvert(string $sourcePath, string $destinationPath, array $options = []): ?array;
}
