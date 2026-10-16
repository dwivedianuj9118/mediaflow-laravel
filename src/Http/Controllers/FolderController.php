<?php

namespace MediaFlow\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MediaFlow\Laravel\Facades\MediaFlow;
use MediaFlow\Laravel\Models\MediaFolder;

class FolderController extends Controller
{
    /**
     * List folders.
     */
    public function index(Request $request): JsonResponse
    {
        $parentId = $request->input('parent_id');
        if ($parentId === 'null' || $parentId === 'undefined' || $parentId === '') {
            $parentId = null;
        } elseif (is_numeric($parentId)) {
            $parentId = (int) $parentId;
        }
        $folders = MediaFolder::where('parent_id', $parentId)
            ->withCount(['children', 'media'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'folders' => $folders,
        ]);
    }

    /**
     * Create a new folder.
     */
    public function store(Request $request): JsonResponse
    {
        $foldersTable = (new MediaFolder)->getTable();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\x00-\x1F\x7F]/'],
            'parent_id' => "nullable|integer|exists:{$foldersTable},id",
        ]);

        if (!empty($validated['parent_id'])) {
            MediaFolder::findOrFail((int) $validated['parent_id']); // respects ownership scoping
        }

        // Prevent duplicate folder names in the same parent folder
        $existing = MediaFolder::where('parent_id', $validated['parent_id'] ?? null)
            ->where('name', trim($validated['name']))
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => "A folder named '{$validated['name']}' already exists here.",
            ], 422);
        }

        $folder = MediaFlow::createFolder($validated['name'], isset($validated['parent_id']) ? (int) $validated['parent_id'] : null);

        return response()->json([
            'success' => true,
            'folder' => $folder,
        ]);
    }

    /**
     * Rename or move a folder.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $folder = MediaFolder::findOrFail($id);

        $foldersTable = (new MediaFolder)->getTable();
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120', 'not_regex:/[\x00-\x1F\x7F]/'],
            'parent_id' => "nullable|integer|exists:{$foldersTable},id",
        ]);

        if (!empty($validated['parent_id'])) {
            MediaFolder::findOrFail((int) $validated['parent_id']);
        }

        // If changing parent, enforce circular hierarchy validation
        if ($request->has('parent_id')) {
            $newParentId = $validated['parent_id'] !== null ? (int) $validated['parent_id'] : null;
            if (!$folder->canMoveTo($newParentId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot move a folder into itself or one of its descendant folders.',
                ], 422);
            }
            $folder->parent_id = $newParentId;
        }

        if (!empty($validated['name'])) {
            $folder->name = trim($validated['name']);
        }

        if ($folder->isDirty(['name', 'parent_id'])) {
            $clash = MediaFolder::where('parent_id', $folder->parent_id)
                ->where('name', $folder->name)
                ->where('id', '!=', $folder->id)
                ->exists();

            if ($clash) {
                return response()->json([
                    'success' => false,
                    'error' => "A folder named '{$folder->name}' already exists there.",
                ], 422);
            }
        }

        $folder->save();

        return response()->json([
            'success' => true,
            'folder' => $folder,
        ]);
    }

    /**
     * Delete a folder.
     */
    public function destroy(int $id): JsonResponse
    {
        $folder = MediaFolder::findOrFail($id);

        try {
            // Cascade move any child files to parent or root to avoid unlinked orphans
            $folder->media()->update(['folder_id' => $folder->parent_id]);
            foreach ($folder->children()->get() as $child) {
                $child->parent_id = $folder->parent_id; // saving recomputes the child's path
                $child->save();
            }

            $folder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Folder deleted successfully.',
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
                    ? 'Warning: This folder cannot be deleted because it is referenced by other database records.'
                    : 'Database constraint prevented folder deletion.',
                'is_relation_warning' => $isRelation,
            ], 409);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
