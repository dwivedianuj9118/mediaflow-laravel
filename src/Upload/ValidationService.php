<?php

namespace MediaFlow\Laravel\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use MediaFlow\Laravel\Support\MediaFlowSettingsManager;
use Symfony\Component\Mime\MimeTypes;

class ValidationService
{
    /**
     * Validate an uploaded file against security, size, and type constraints.
     *
     * @return array{valid: bool, error: ?string}
     */
    public function validateFile(UploadedFile $file, array $customRules = []): array
    {
        // 1. Check for upload errors
        if (!$file->isValid()) {
            return [
                'valid' => false,
                'error' => 'File upload was incomplete or encountered a transport error: ' . $file->getErrorMessage(),
            ];
        }

        $filename = $file->getClientOriginalName();

        // 2. Null byte, control character and directory traversal protection
        if ($filename === ''
            || mb_strlen($filename) > 255
            || preg_match('/[\x00-\x1F\x7F]/', $filename)
            || str_contains($filename, '..')
            || str_contains($filename, '/')
            || str_contains($filename, '\\')) {
            return [
                'valid' => false,
                'error' => 'Filename contains disallowed characters or path traversal sequences.',
            ];
        }

        // 3. Block dangerous executable extensions (client-supplied and MIME-derived)
        $blocked = array_map('strtolower', config('mediaflow.blocked_extensions', []));
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $blocked, true)) {
            return [
                'valid' => false,
                'error' => "Uploading executable files with .{$extension} extension is forbidden.",
            ];
        }

        // 4. File size limit (explicit rule > admin setting > config)
        $maxKb = (int) ($customRules['max_kb'] ?? $this->settingValue('upload.max_file_size_kb') ?? config('mediaflow.max_file_size_kb', 20480));
        $fileSizeKb = $file->getSize() / 1024;
        if ($maxKb > 0 && $fileSizeKb > $maxKb) {
            $formattedMax = round($maxKb / 1024, 1) . ' MB';
            return [
                'valid' => false,
                'error' => "File size exceeds maximum allowed threshold of {$formattedMax}.",
            ];
        }

        // 5. Allowed MIME types validation (server-side detection, never the client header)
        $detectedMime = $file->getMimeType() ?: 'application/octet-stream';
        $allowedMimes = $customRules['allowed_mimes'] ?? config('mediaflow.allowed_mimes', []);

        if (!empty($allowedMimes) && !in_array($detectedMime, $allowedMimes, true)) {
            // Check wildcard matching e.g. "image/*"
            $matched = false;
            foreach ($allowedMimes as $allowed) {
                if ($allowed === '*/*' || (Str::endsWith($allowed, '/*') && Str::startsWith($detectedMime, Str::before($allowed, '/*') . '/'))) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                return [
                    'valid' => false,
                    'error' => "MIME type [{$detectedMime}] is not allowed.",
                ];
            }
        }

        $storedExtension = $this->safeExtension($file);
        if (in_array($storedExtension, $blocked, true)) {
            return [
                'valid' => false,
                'error' => "Files detected as [{$detectedMime}] are not allowed.",
            ];
        }

        // 6. SVG validation: reject scripts, event handlers, external references and embedded HTML
        if ($detectedMime === 'image/svg+xml' && config('mediaflow.sanitize_svg', true)) {
            $contents = (string) file_get_contents($file->getRealPath());
            if ($this->svgIsUnsafe($contents)) {
                return [
                    'valid' => false,
                    'error' => 'SVG file contains potentially malicious embedded scripts.',
                ];
            }
        }

        // 7. Decompression-bomb guard for raster images
        if (Str::startsWith($detectedMime, 'image/') && $detectedMime !== 'image/svg+xml') {
            $maxMegapixels = (float) config('mediaflow.max_image_megapixels', 50);
            $info = @getimagesize($file->getRealPath());
            if ($maxMegapixels > 0 && $info && isset($info[0], $info[1])
                && ((int) $info[0] * (int) $info[1]) > $maxMegapixels * 1_000_000) {
                return [
                    'valid' => false,
                    'error' => "Image dimensions exceed the maximum of {$maxMegapixels} megapixels.",
                ];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Extension used for the stored object. It is derived from the server-detected
     * MIME type so a file can never be stored as .html/.php just because the client
     * named it that way. The client extension is kept only when it is a valid
     * extension for the detected type (e.g. ".jpeg" vs ".jpg").
     */
    public function safeExtension(UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $clientExt = strtolower($file->getClientOriginalExtension());

        $validExtensions = class_exists(MimeTypes::class)
            ? MimeTypes::getDefault()->getExtensions($mime)
            : [];

        if ($clientExt !== '' && in_array($clientExt, $validExtensions, true)) {
            return $clientExt;
        }

        $guessed = $validExtensions[0] ?? $file->guessExtension();

        return $guessed ? strtolower($guessed) : 'bin';
    }

    /**
     * Heuristic SVG safety check run on both raw and entity-decoded markup.
     */
    public function svgIsUnsafe(string $contents): bool
    {
        $variants = [$contents, html_entity_decode($contents, ENT_QUOTES | ENT_HTML5, 'UTF-8')];

        $patterns = [
            '/<\s*script/i',
            '/<\s*foreignObject/i',
            '/<\s*(iframe|embed|object|frame|frameset|meta|link|base)\b/i',
            '/<!ENTITY/i',
            '/<!DOCTYPE[^>]*\[/i',
            '/\bon[a-z]+\s*=/i',
            '/(java|vb)script\s*:/i',
            '/data\s*:/i',
            '/\b(?:xlink:)?href\s*=\s*["\']?\s*(?!#)/i',
            '/@import/i',
        ];

        foreach ($variants as $markup) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $markup)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function settingValue(string $key)
    {
        try {
            $value = app(MediaFlowSettingsManager::class)->getPersisted($key);
        } catch (\Throwable $e) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
