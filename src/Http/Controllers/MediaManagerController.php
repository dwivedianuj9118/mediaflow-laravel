<?php

namespace MediaFlow\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MediaFlow\Laravel\Facades\MediaFlow;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaFolder;

class MediaManagerController extends Controller
{
    /**
     * Browse media items and folders or display the manager UI.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return $this->jsonData($request);
        }

        return view('mediaflow::pages.manager');
    }

    /**
     * Display the 3D upload lifecycle & pipeline visualizer.
     */
    public function pipeline()
    {
        return view('mediaflow::pages.pipeline');
    }

    /**
     * Display the comprehensive documentation, installation guide, and code references.
     */
    public function docs()
    {
        return view('mediaflow::pages.docs');
    }

    /**
     * Return JSON data for media manager and picker components.
     */
    public function jsonData(Request $request): JsonResponse
    {
        $rawFolder = $request->input('folder_id');
        if ($rawFolder === null || $rawFolder === '' || $rawFolder === 'null' || $rawFolder === 'undefined') {
            $rawFolder = $request->input('folder');
        }

        if ($rawFolder === null || $rawFolder === '' || $rawFolder === 'null' || $rawFolder === 'undefined') {
            $folderId = null;
        } elseif (is_numeric($rawFolder)) {
            $folderId = (int) $rawFolder;
        } else {
            $folderModel = MediaFolder::where('name', $rawFolder)
                ->orWhere('slug', \Illuminate\Support\Str::slug($rawFolder))
                ->first();
            $folderId = $folderModel ? $folderModel->id : null;
        }

        $search = $request->input('search');
        $collection = $request->input('collection');
        $type = $request->input('type');
        $sortField = $request->input('sort', 'created_at');
        $sortOrder = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Allowed sort columns to prevent SQL injection
        if (!in_array($sortField, ['created_at', 'original_name', 'size_bytes'], true)) {
            $sortField = 'created_at';
        }

        $scope = $request->input('scope');
        $isolated = $request->boolean('isolated');

        // Folders in current directory
        if (($scope === 'frontend' || $isolated) && $folderId === null && !empty($collection)) {
            // In frontend/isolated mode scoped to a collection at root, hide root admin folders
            $folders = collect();
        } elseif (($scope === 'frontend' || $isolated) && $folderId === null) {
            $folders = MediaFolder::where('parent_id', null)
                ->where('slug', 'not like', 'admin%')
                ->where('name', 'not like', 'admin%')
                ->withCount(['children', 'media'])
                ->orderBy('name')
                ->get();
        } else {
            $folders = MediaFolder::where('parent_id', $folderId)
                ->withCount(['children', 'media'])
                ->orderBy('name')
                ->get();
        }

        // Media query
        $mediaQuery = Media::query()->with('variants');

        // Only filter by folder if not searching globally or if isolated
        if (!empty($search)) {
            $mediaQuery->search($search);
            if ($isolated || $scope === 'frontend') {
                if ($folderId !== null) {
                    $mediaQuery->where('folder_id', $folderId);
                }
            }
        } else {
            $mediaQuery->where('folder_id', $folderId);
        }

        if (!empty($collection)) {
            $mediaQuery->where('collection', $collection);
        }

        // Apply strict frontend isolation so admin files are not exposed to client forms
        if ($scope === 'frontend' || $isolated) {
            if ($folderId === null && empty($collection)) {
                if (auth()->check()) {
                    $mediaQuery->where(function ($q) {
                        $q->where('owner_id', (string) auth()->id())
                          ->orWhere('visibility', 'public');
                    });
                } else {
                    $mediaQuery->where('visibility', 'public')
                               ->where('collection', '!=', 'admin');
                }
            }
        }

        $accept = $request->input('accept');

        // Filter by MIME type or accept rule
        if ($type === 'image' || ($accept && (str_contains($accept, 'image/*') || $accept === 'image'))) {
            $mediaQuery->where('mime_type', 'like', 'image/%');
        } elseif ($type === 'video' || ($accept && (str_contains($accept, 'video/*') || str_contains($accept, '.mp4')))) {
            $mediaQuery->where('mime_type', 'like', 'video/%');
        } elseif ($type === 'audio' || ($accept && (str_contains($accept, 'audio/*') || str_contains($accept, '.mp3')))) {
            $mediaQuery->where('mime_type', 'like', 'audio/%');
        } elseif (in_array($type, ['doc', 'docs', 'document', 'documents', 'pdf'], true) || ($accept && (str_contains($accept, 'pdf') || str_contains($accept, '.doc') || str_contains($accept, 'document')))) {
            $mediaQuery->where(function ($q) {
                $q->where('mime_type', 'application/pdf')
                  ->orWhere('mime_type', 'like', '%word%')
                  ->orWhere('mime_type', 'like', '%excel%')
                  ->orWhere('mime_type', 'like', '%spreadsheet%')
                  ->orWhere('mime_type', 'like', '%powerpoint%')
                  ->orWhere('mime_type', 'like', '%presentation%')
                  ->orWhere('mime_type', 'like', 'text/%')
                  ->orWhereIn('extension', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt']);
            });
        } elseif (!empty($accept) && $accept !== '*' && $accept !== '*/*') {
            // Comma-separated extension or mime filters e.g. ".pdf,.png,.zip"
            $tokens = array_filter(array_map('trim', explode(',', $accept)));
            $extensions = [];
            $mimeWildcards = [];
            $exactMimes = [];

            foreach ($tokens as $token) {
                if (str_starts_with($token, '.')) {
                    $extensions[] = strtolower(ltrim($token, '.'));
                } elseif (str_ends_with($token, '/*')) {
                    $mimeWildcards[] = substr($token, 0, -1) . '%';
                } elseif (str_contains($token, '/')) {
                    $exactMimes[] = $token;
                }
            }

            if (!empty($extensions) || !empty($mimeWildcards) || !empty($exactMimes)) {
                $mediaQuery->where(function ($q) use ($extensions, $mimeWildcards, $exactMimes) {
                    if (!empty($extensions)) {
                        $q->whereIn('extension', $extensions);
                    }
                    foreach ($mimeWildcards as $wildcard) {
                        $q->orWhere('mime_type', 'like', $wildcard);
                    }
                    if (!empty($exactMimes)) {
                        $q->orWhereIn('mime_type', $exactMimes);
                    }
                });
            }
        }

        $mediaPaginator = $mediaQuery->orderBy($sortField, $sortOrder)
            ->paginate((int) config('mediaflow.ui.items_per_page', 24));

        // Format items with URLs
        $items = $mediaPaginator->getCollection()->map(function (Media $media) {
            $ext = strtolower($media->extension ?? '');
            $mime = strtolower($media->mime_type ?? '');
            $isVideo = str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'ogg', 'mov'], true);
            $isAudio = str_starts_with($mime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'], true);
            $isDoc = $mime === 'application/pdf' || in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'], true);

            return [
                'id' => $media->id,
                'uuid' => $media->uuid,
                'name' => $media->original_name,
                'original_name' => $media->original_name,
                'extension' => $media->extension,
                'mime_type' => $media->mime_type,
                'size_bytes' => $media->size_bytes,
                'human_size' => $media->humanSize(),
                'width' => $media->width,
                'height' => $media->height,
                'disk' => $media->disk,
                'folder_id' => $media->folder_id,
                'status' => $media->status,
                'visibility' => $media->visibility,
                'url' => $media->isReady() ? $media->url() : null,
                'thumbnail_url' => $media->isReady() ? $media->thumbnailUrl('thumb') : null,
                'is_image' => $media->isImage(),
                'is_video' => $isVideo,
                'is_audio' => $isAudio,
                'is_document' => $isDoc,
                'created_at' => $media->created_at ? $media->created_at->toIso8601String() : null,
            ];
        });

        // Breadcrumbs trail
        $breadcrumbs = [];
        if ($folderId !== null) {
            $currentFolder = MediaFolder::find($folderId);
            if ($currentFolder) {
                $breadcrumbs = $currentFolder->breadcrumbs();
            }
        }

        return response()->json([
            'success' => true,
            'current_folder_id' => $folderId,
            'breadcrumbs' => $breadcrumbs,
            'folders' => $folders,
            'media' => $items,
            'pagination' => [
                'total' => $mediaPaginator->total(),
                'per_page' => $mediaPaginator->perPage(),
                'current_page' => $mediaPaginator->currentPage(),
                'last_page' => $mediaPaginator->lastPage(),
            ],
        ]);
    }

    /**
     * Get single media details.
     */
    public function show(int $id): JsonResponse
    {
        $media = Media::with('variants', 'folder')->findOrFail($id);

        return response()->json([
            'success' => true,
            'media' => [
                'id' => $media->id,
                'uuid' => $media->uuid,
                'original_name' => $media->original_name,
                'mime_type' => $media->mime_type,
                'size_bytes' => $media->size_bytes,
                'human_size' => $media->humanSize(),
                'width' => $media->width,
                'height' => $media->height,
                'disk' => $media->disk,
                'storage_key' => $media->storage_key,
                'status' => $media->status,
                'visibility' => $media->visibility,
                'url' => $media->url(),
                'folder' => $media->folder ? ['id' => $media->folder->id, 'name' => $media->folder->name] : null,
                'variants' => $media->variants->map(fn($v) => [
                    'preset' => $v->preset,
                    'url' => $v->url(),
                    'width' => $v->width,
                    'height' => $v->height,
                ]),
                'created_at' => $media->created_at->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Rename or move a media item.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $media = Media::findOrFail($id);

        $request->validate([
            'name' => ['nullable', 'string', 'max:255', 'not_regex:/[\x00-\x1F\x7F\/\\\\]/'],
            'folder_id' => 'nullable|integer',
        ]);

        if ($request->has('name') && !empty($request->input('name'))) {
            MediaFlow::rename($media, $request->input('name'));
        }

        if ($request->has('folder_id')) {
            $folderId = $request->input('folder_id') !== null && $request->input('folder_id') !== ''
                ? (int) $request->input('folder_id')
                : null;
            MediaFlow::move($media, $folderId);
        }

        return response()->json([
            'success' => true,
            'media' => $media->fresh(),
        ]);
    }

    /**
     * Delete a single media item.
     */
    public function destroy(int $id): JsonResponse
    {
        $media = Media::findOrFail($id);

        try {
            MediaFlow::delete($media);

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully.',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $sqlMessage = $e->getMessage();
            $isRelation = str_contains($sqlMessage, 'FOREIGN KEY') ||
                          str_contains($sqlMessage, 'foreign key constraint') ||
                          str_contains($sqlMessage, 'Integrity constraint violation') ||
                          str_contains($sqlMessage, '1451') ||
                          str_contains($sqlMessage, '23000');

            return response()->json([
                'success' => false,
                'error' => $isRelation
                    ? 'Warning: This image cannot be deleted because it is currently linked to other records or tables in your application.'
                    : 'Database constraint prevented media deletion.',
                'is_relation_warning' => $isRelation,
            ], 409);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Optimize, convert or resize an image.
     */
    public function optimize(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quality' => 'nullable|integer|min:30|max:100',
            'format' => 'nullable|string|in:webp,jpeg,jpg,png',
            'width' => 'nullable|integer|min:20|max:8000',
            'height' => 'nullable|integer|min:20|max:8000',
            'max_width' => 'nullable|integer|min:20|max:8000',
            'max_height' => 'nullable|integer|min:20|max:8000',
            'action' => 'nullable|string|in:replace,copy',
        ]);

        if (empty($validated['width']) && !empty($validated['max_width'])) {
            $validated['width'] = $validated['max_width'];
        }
        if (empty($validated['height']) && !empty($validated['max_height'])) {
            $validated['height'] = $validated['max_height'];
        }

        try {
            $result = MediaFlow::optimize($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Image optimized successfully.',
                'metrics' => [
                    'size_before' => $result['size_before'],
                    'size_after' => $result['size_after'],
                    'savings_percent' => $result['savings_percent'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                    'format' => $result['format'],
                    'mime' => $result['mime'],
                ],
                'media' => [
                    'id' => $result['media']->id,
                    'uuid' => $result['media']->uuid,
                    'name' => $result['media']->original_name,
                    'mime_type' => $result['media']->mime_type,
                    'size_bytes' => $result['media']->size_bytes,
                    'human_size' => $result['media']->humanSize(),
                    'width' => $result['media']->width,
                    'height' => $result['media']->height,
                    'disk' => $result['media']->disk,
                    'status' => $result['media']->status,
                    'visibility' => $result['media']->visibility,
                    'url' => $result['media']->url(),
                    'thumbnail_url' => $result['media']->thumbnailUrl('thumb'),
                    'is_image' => $result['media']->isImage(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            $message = 'Image optimization failed. Check the application log for details.';

            return response()->json([
                'success' => false,
                'error' => $message,
                'message' => $message,
            ], 422);
        }
    }

    /**
     * Bulk actions (move or delete).
     */
    public function bulk(Request $request): JsonResponse
    {
        $foldersTable = (new MediaFolder)->getTable();
        $request->validate([
            'action' => 'required|in:delete,move',
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer',
            'target_folder_id' => "nullable|exists:{$foldersTable},id",
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        if ($action === 'delete') {
            $mediaList = Media::whereIn('id', $ids)->get();
            $deletedCount = 0;
            $skippedCount = 0;
            $hasRelationWarning = false;

            foreach ($mediaList as $media) {
                try {
                    MediaFlow::delete($media);
                    $deletedCount++;
                } catch (\Illuminate\Database\QueryException $e) {
                    $skippedCount++;
                    $hasRelationWarning = true;
                } catch (\Throwable $e) {
                    $skippedCount++;
                }
            }

            if ($deletedCount === 0 && $skippedCount > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Warning: The selected image(s) cannot be deleted because they are currently linked to other records or tables in your database.',
                    'is_relation_warning' => true,
                    'skipped_count' => $skippedCount,
                ], 409);
            }

            $message = $deletedCount . ' ' . ($deletedCount === 1 ? 'item' : 'items') . ' deleted successfully.';
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} skipped due to active table relations).";
            }

            return response()->json([
                'success' => true,
                'deleted_count' => $deletedCount,
                'skipped_count' => $skippedCount,
                'message' => $message,
                'warning' => $hasRelationWarning ? "{$skippedCount} item(s) could not be deleted because they are referenced in other tables." : null,
            ]);
        }

        if ($action === 'move') {
            $targetFolderId = $request->input('target_folder_id');
            if ($targetFolderId !== null) {
                MediaFolder::findOrFail((int) $targetFolderId); // respects ownership scoping
            }
            $movedCount = Media::whereIn('id', $ids)->update(['folder_id' => $targetFolderId]);

            return response()->json([
                'success' => true,
                'moved_count' => $movedCount,
                'message' => $movedCount . ' ' . ($movedCount === 1 ? 'item' : 'items') . ' moved successfully.',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid action.'], 400);
    }
}
