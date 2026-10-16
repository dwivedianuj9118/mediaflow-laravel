<?php

namespace MediaFlow\Laravel\Processing;

use MediaFlow\Laravel\Contracts\ImageProcessor;

class GdImageProcessor implements ImageProcessor
{
    public function isSupported(): bool
    {
        return extension_loaded('gd');
    }

    public function extractDimensions(string $realPath): ?array
    {
        if (!file_exists($realPath) || !is_readable($realPath)) {
            return null;
        }

        $info = @getimagesize($realPath);
        if (!$info || !isset($info[0], $info[1])) {
            return null;
        }

        return [
            'width' => (int) $info[0],
            'height' => (int) $info[1],
        ];
    }

    public function stripExif(string $sourcePath, string $destinationPath): bool
    {
        if (!$this->isSupported() || !file_exists($sourcePath)) {
            return false;
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return false;
        }

        $image = $this->createImageResource($sourcePath, $info[2]);
        if (!$image) {
            return false;
        }

        // Re-encoding drops the EXIF Orientation tag, so bake the rotation into the pixels first.
        if ($info[2] === IMAGETYPE_JPEG) {
            $image = $this->applyExifOrientation($image, $sourcePath);
        }

        if (in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $success = $this->saveImageResource($image, $destinationPath, $info[2], 90);
        imagedestroy($image);

        return $success;
    }

    /**
     * Rotate/flip a JPEG according to its EXIF Orientation tag.
     *
     * @param  \GdImage  $image
     * @return \GdImage
     */
    protected function applyExifOrientation($image, string $path)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3, 4 => imagerotate($image, 180, 0),
            5, 6 => imagerotate($image, -90, 0),
            7, 8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated === false) {
            return $image;
        }

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($rotated, IMG_FLIP_HORIZONTAL);
        }

        if ($rotated !== $image) {
            imagedestroy($image);
        }

        return $rotated;
    }

    public function createPreset(string $sourcePath, string $destinationPath, array $presetConfig): ?array
    {
        if (!$this->isSupported() || !file_exists($sourcePath)) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return null;
        }

        $origW = (int) $info[0];
        $origH = (int) $info[1];
        $type = $info[2];

        $targetW = (int) ($presetConfig['width'] ?? 200);
        $targetH = (int) ($presetConfig['height'] ?? 200);
        $fit = $presetConfig['fit'] ?? 'contain';
        $format = strtolower($presetConfig['format'] ?? 'webp');
        $quality = (int) ($presetConfig['quality'] ?? 80);

        $srcImage = $this->createImageResource($sourcePath, $type);
        if (!$srcImage) {
            return null;
        }

        if ($fit === 'crop') {
            // Center crop with cover aspect ratio
            $srcRatio = $origW / $origH;
            $dstRatio = $targetW / $targetH;

            if ($srcRatio > $dstRatio) {
                $cropW = (int) round($origH * $dstRatio);
                $cropH = $origH;
                $srcX = (int) round(($origW - $cropW) / 2);
                $srcY = 0;
            } else {
                $cropW = $origW;
                $cropH = (int) round($origW / $dstRatio);
                $srcX = 0;
                $srcY = (int) round(($origH - $cropH) / 2);
            }

            $dstImage = imagecreatetruecolor($targetW, $targetH);
            $this->preserveAlpha($dstImage);

            imagecopyresampled($dstImage, $srcImage, 0, 0, $srcX, $srcY, $targetW, $targetH, $cropW, $cropH);
            $finalW = $targetW;
            $finalH = $targetH;
        } else {
            // Contain: maintain aspect ratio within target boundaries
            $ratio = min($targetW / $origW, $targetH / $origH);
            $finalW = max(1, (int) round($origW * $ratio));
            $finalH = max(1, (int) round($origH * $ratio));

            $dstImage = imagecreatetruecolor($finalW, $finalH);
            $this->preserveAlpha($dstImage);

            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $finalW, $finalH, $origW, $origH);
        }

        // Save in requested format
        $mime = 'image/webp';
        $saved = false;

        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($format === 'webp' && function_exists('imagewebp')) {
            $saved = imagewebp($dstImage, $destinationPath, $quality);
            $mime = 'image/webp';
        } elseif ($format === 'png' || $type === IMAGETYPE_PNG) {
            $pngQuality = (int) round(9 * (100 - $quality) / 100);
            $saved = imagepng($dstImage, $destinationPath, $pngQuality);
            $mime = 'image/png';
        } else {
            $saved = imagejpeg($dstImage, $destinationPath, $quality);
            $mime = 'image/jpeg';
        }

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        if (!$saved || !file_exists($destinationPath)) {
            return null;
        }

        return [
            'width' => $finalW,
            'height' => $finalH,
            'size' => filesize($destinationPath),
            'mime' => $mime,
        ];
    }

    public function optimizeAndConvert(string $sourcePath, string $destinationPath, array $options = []): ?array
    {
        if (!$this->isSupported() || !file_exists($sourcePath)) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return null;
        }

        $origW = (int) $info[0];
        $origH = (int) $info[1];
        $type = $info[2];
        $sizeBefore = filesize($sourcePath);

        $quality = (int) ($options['quality'] ?? 82);
        $format = strtolower($options['format'] ?? 'webp');
        if ($format === 'jpg') $format = 'jpeg';

        $targetW = !empty($options['width']) ? (int) $options['width'] : $origW;
        $targetH = !empty($options['height']) ? (int) $options['height'] : $origH;

        $srcImage = $this->createImageResource($sourcePath, $type);
        if (!$srcImage) {
            return null;
        }

        // Maintain aspect ratio if dimensions changed
        if ($targetW !== $origW || $targetH !== $origH) {
            $ratio = min($targetW / $origW, $targetH / $origH);
            $finalW = max(1, (int) round($origW * $ratio));
            $finalH = max(1, (int) round($origH * $ratio));

            $dstImage = imagecreatetruecolor($finalW, $finalH);
            $this->prepareCanvas($dstImage, $format);
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $finalW, $finalH, $origW, $origH);
        } else {
            $finalW = $origW;
            $finalH = $origH;
            $dstImage = imagecreatetruecolor($finalW, $finalH);
            $this->prepareCanvas($dstImage, $format);
            imagecopy($dstImage, $srcImage, 0, 0, 0, 0, $finalW, $finalH);
        }

        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $saved = false;
        $mime = 'image/webp';

        if ($format === 'webp' && function_exists('imagewebp')) {
            $saved = imagewebp($dstImage, $destinationPath, $quality);
            $mime = 'image/webp';
        } elseif ($format === 'png') {
            $pngQuality = (int) round(9 * (100 - $quality) / 100);
            $saved = imagepng($dstImage, $destinationPath, $pngQuality);
            $mime = 'image/png';
        } else {
            $saved = imagejpeg($dstImage, $destinationPath, $quality);
            $mime = 'image/jpeg';
        }

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        if (!$saved || !file_exists($destinationPath)) {
            return null;
        }

        $sizeAfter = filesize($destinationPath);
        $savings = $sizeBefore > 0 ? round((1 - ($sizeAfter / $sizeBefore)) * 100, 1) : 0;

        return [
            'size_before' => $sizeBefore,
            'size_after' => $sizeAfter,
            'width' => $finalW,
            'height' => $finalH,
            'mime' => $mime,
            'format' => $format,
            'savings_percent' => $savings,
        ];
    }

    protected function createImageResource(string $path, int $imageType)
    {
        $info = @getimagesize($path);
        $maxMegapixels = (float) config('mediaflow.max_image_megapixels', 50);
        if ($info && $maxMegapixels > 0 && ((int) $info[0] * (int) $info[1]) > $maxMegapixels * 1_000_000) {
            return null; // refuse decompression bombs
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($path);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null;
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($path);
            default:
                return null;
        }
    }

    protected function saveImageResource($resource, string $destinationPath, int $imageType, int $quality): bool
    {
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagejpeg($resource, $destinationPath, $quality);
            case IMAGETYPE_PNG:
                return imagepng($resource, $destinationPath, 6);
            case IMAGETYPE_WEBP:
                return function_exists('imagewebp') ? imagewebp($resource, $destinationPath, $quality) : false;
            case IMAGETYPE_GIF:
                return imagegif($resource, $destinationPath);
            default:
                return false;
        }
    }

    /**
     * JPEG has no alpha channel: paint a white background so transparent areas
     * do not turn black. Other formats keep transparency.
     */
    protected function prepareCanvas($image, string $format): void
    {
        if ($format === 'jpeg' || $format === 'jpg') {
            imagealphablending($image, true);
            $white = imagecolorallocate($image, 255, 255, 255);
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $white);
            return;
        }

        $this->preserveAlpha($image);
    }

    protected function preserveAlpha($image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
    }
}
