<?php

namespace MediaFlow\Laravel\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MediaFolder extends Model
{
    protected $table = 'mediaflow_folders';

    public function __construct(array $attributes = [])
    {
        $this->table = config('mediaflow.tables.folders', 'mediaflow_folders');
        parent::__construct($attributes);
    }

    public function getTable()
    {
        return config('mediaflow.tables.folders', parent::getTable() ?: 'mediaflow_folders');
    }

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'path',
        'owner_id',
        'tenant_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new Scopes\OwnerScope());

        static::saving(function (MediaFolder $folder) {
            if (empty($folder->slug) || $folder->isDirty('name')) {
                $folder->slug = static::makeSlug((string) $folder->name);
            }
            if ($folder->isDirty('parent_id')) {
                $folder->unsetRelation('parent');
            }
            $folder->path = $folder->calculatePath();
        });

        // Keep descendants' materialized paths in sync after a rename or move.
        static::saved(function (MediaFolder $folder) {
            if ($folder->wasChanged('path')) {
                foreach ($folder->children()->get() as $child) {
                    $child->setRelation('parent', $folder);
                    $child->path = $child->calculatePath();
                    $child->save();
                }
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id');
    }

    /**
     * Slug for a folder name; falls back to a short hash for names with no ASCII letters.
     */
    public static function makeSlug(string $name): string
    {
        return Str::slug($name) ?: 'folder-' . substr(md5($name), 0, 8);
    }

    /**
     * Compute normalized path hierarchy e.g. "blog/covers".
     */
    public function calculatePath(): string
    {
        $segments = [$this->slug ?: static::makeSlug((string) $this->name)];
        $current = $this->parent;
        $guard = 0;

        while ($current && $guard++ < 100) {
            array_unshift($segments, $current->slug ?: static::makeSlug((string) $current->name));
            $current = $current->parent;
        }

        return implode('/', $segments);
    }

    /**
     * Retrieve hierarchical breadcrumb trail.
     *
     * @return array<int, array{id: int, name: string, path: string}>
     */
    public function breadcrumbs(): array
    {
        $trail = [];
        $current = $this;

        $guard = 0;
        while ($current && $guard++ < 100) {
            array_unshift($trail, [
                'id' => $current->id,
                'name' => $current->name,
                'path' => $current->path ?? '',
            ]);
            $current = $current->parent;
        }

        return $trail;
    }

    /**
     * Get IDs of all descendants recursively.
     *
     * @return array<int>
     */
    public function allDescendantIds(): array
    {
        $ids = [];
        $children = $this->children;

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->allDescendantIds());
        }

        return $ids;
    }

    /**
     * Validate whether this folder can be moved to the target parent.
     * Prevents moving inside itself or any of its descendants.
     */
    public function canMoveTo(?int $targetParentId): bool
    {
        if ($targetParentId === null) {
            return true; // Can move to root
        }

        if ($targetParentId === $this->id) {
            return false; // Cannot be parent of itself
        }

        return !in_array($targetParentId, $this->allDescendantIds(), true);
    }
}
