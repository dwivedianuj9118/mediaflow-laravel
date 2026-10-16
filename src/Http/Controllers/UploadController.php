<?php

namespace MediaFlow\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MediaFlow\Laravel\Facades\MediaFlow;
use MediaFlow\Laravel\Exceptions\MediaUploadException;
use MediaFlow\Laravel\Models\Media;

class UploadController extends Controller
{
    /**
     * Handle single or multi-file uploads.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'folder' => ['nullable', $this->shortScalarRule()],
            'folder_id' => ['nullable', $this->shortScalarRule()],
            'collection' => 'nullable|string|max:100',
            'visibility' => 'nullable|in:public,private',
            'thumbnails' => 'nullable|boolean',
        ]);

        try {
            $file = $request->file('file');
            $folderInput = $request->input('folder_id');
            if ($folderInput === null || $folderInput === '' || $folderInput === 'null' || $folderInput === 'undefined') {
                $folderInput = $request->input('folder');
            }
            if ($folderInput === 'null' || $folderInput === 'undefined' || $folderInput === '') {
                $folderInput = null;
            }

            $options = [
                'folder' => $folderInput,
                'folder_id' => $folderInput,
                'collection' => $request->input('collection', 'default'),
                'visibility' => $request->input('visibility', 'public'),
                'thumbnails' => $request->boolean('thumbnails', true),
                'owner_id' => auth()->check() ? (string) auth()->id() : null,
            ];

            $media = MediaFlow::upload($file, $options);

            $ext = strtolower($media->extension ?? '');
            $mime = strtolower($media->mime_type ?? '');
            $isVideo = str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'ogg', 'mov'], true);
            $isAudio = str_starts_with($mime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'], true);
            $isDoc = $mime === 'application/pdf' || in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'], true);

            return response()->json([
                'success' => true,
                'media' => [
                    'id' => $media->id,
                    'uuid' => $media->uuid,
                    'name' => $media->original_name,
                    'original_name' => $media->original_name,
                    'extension' => $media->extension,
                    'mime_type' => $media->mime_type,
                    'size_bytes' => $media->size_bytes,
                    'human_size' => $media->humanSize(),
                    'url' => $media->url(),
                    'thumbnail_url' => $media->thumbnailUrl('thumb'),
                    'status' => $media->status,
                    'is_image' => $media->isImage(),
                    'is_video' => $isVideo,
                    'is_audio' => $isAudio,
                    'is_document' => $isDoc,
                    'width' => $media->width,
                    'height' => $media->height,
                    'folder_id' => $media->folder_id,
                ],
            ]);
        } catch (MediaUploadException $e) {
            // Validation problems are safe to show; unexpected failures are logged instead
            // of leaking paths, SQL or credentials to the browser.
            if ($e->getPrevious() !== null) {
                report($e);
            }

            return response()->json([
                'success' => false,
                'error' => $e->getPrevious() === null ? $e->getMessage() : 'The file could not be stored. Check the application log for details.',
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'The file could not be uploaded. Check the application log for details.',
            ], 500);
        }
    }

    /**
     * Accepts an integer ID or a folder name/path of at most 255 characters.
     */
    protected function shortScalarRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            if (!is_scalar($value) || mb_strlen((string) $value) > 255) {
                $fail("The {$attribute} must be a folder ID or a name/path of at most 255 characters.");
            }
        };
    }

    /**
     * Check status of a media item.
     */
    public function status(int $id): JsonResponse
    {
        $media = Media::find($id);
        if (!$media) {
            return response()->json(['success' => false, 'error' => 'Media not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $media->status,
            'is_ready' => $media->isReady(),
            'error_message' => $media->error_message,
            'url' => $media->isReady() ? $media->url() : null,
        ]);
    }

    /**
     * Provide a fresh CSRF token for reactive components.
     */
    public function csrfToken(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'csrf_token' => csrf_token(),
        ]);
    }
}
