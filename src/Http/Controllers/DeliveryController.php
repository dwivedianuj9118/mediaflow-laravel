<?php

namespace MediaFlow\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\Scopes\OwnerScope;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryController extends Controller
{
    /**
     * MIME types that are safe to render inline in the browser. Anything else
     * (SVG, HTML-like text, PDFs with scripts, ...) is sent as an attachment.
     */
    protected array $inlineSafe = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif',
        'video/mp4', 'video/quicktime', 'video/webm', 'audio/mpeg', 'audio/ogg',
    ];

    /**
     * Stream a private or signed media file (or one of its variants via ?variant=preset).
     */
    public function stream(Request $request, int $media)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired signature.');
        }

        // The signature is the authorization here, so ownership scoping does not apply.
        $mediaRecord = Media::withoutGlobalScope(OwnerScope::class)->findOrFail($media);

        $diskName = $mediaRecord->disk;
        $storageKey = $mediaRecord->storage_key;
        $mime = $mediaRecord->mime_type;
        $size = $mediaRecord->size_bytes;
        $filename = $mediaRecord->original_name;

        if ($preset = $request->query('variant')) {
            $variant = $mediaRecord->variants()->where('preset', (string) $preset)->firstOrFail();
            $diskName = $variant->disk;
            $storageKey = $variant->storage_key;
            $mime = $variant->mime_type;
            $size = $variant->size_bytes;
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '_' . $variant->preset . '.' . pathinfo($storageKey, PATHINFO_EXTENSION);
        }

        $disk = Storage::disk($diskName);

        if (!$disk->exists($storageKey)) {
            abort(404, 'File not found on storage.');
        }

        $stream = $disk->readStream($storageKey);
        $actualSize = (int) ($disk->size($storageKey) ?: $size);

        $dispositionType = in_array($mime, $this->inlineSafe, true)
            ? HeaderUtils::DISPOSITION_INLINE
            : HeaderUtils::DISPOSITION_ATTACHMENT;

        $filename = str_replace(['/', '\\'], '_', (string) $filename) ?: 'download';
        $fallbackName = preg_replace('/[^\x20-\x7E]|[%\/\\\\"]/', '_', $filename) ?: 'download';

        return new StreamedResponse(
            function () use ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type' => $mime,
                'Content-Length' => (string) $actualSize,
                'Content-Disposition' => HeaderUtils::makeDisposition($dispositionType, $filename, $fallbackName),
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; media-src 'self'; style-src 'unsafe-inline'; sandbox",
                'Cache-Control' => 'private, no-transform, max-age=1800',
            ]
        );
    }
}
