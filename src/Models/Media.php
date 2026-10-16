<?php

namespace MediaFlow\Laravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use MediaFlow\Laravel\Contracts\DeliveryAdapter;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'mediaflow_media';

    public function __construct(array $attributes = [])
    {
        $this->table = config('mediaflow.tables.media', 'mediaflow_media');
        parent::__construct($attributes);
    }

    public function getTable()
    {
        return config('mediaflow.tables.media', parent::getTable() ?: 'mediaflow_media');
    }

    protected $fillable = [
        'uuid',
        'folder_id',
        'owner_id',
        'tenant_id',
        'collection',
        'original_name',
        'disk',
        'storage_key',
        'mime_type',
        'extension',
        'size_bytes',
        'checksum',
        'width',
        'height',
        'visibility',
        'status',
        'error_message',
        'metadata',
        'uploaded_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new Scopes\OwnerScope());

        static::creating(function (Media $media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MediaVariant::class, 'media_id');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isImage(): bool
    {
        return Str::startsWith($this->mime_type, 'image/');
    }

    public function isSvg(): bool
    {
        return $this->mime_type === 'image/svg+xml';
    }

    public function isVideo(): bool
    {
        return Str::startsWith($this->mime_type, 'video/');
    }

    public function isAudio(): bool
    {
        return Str::startsWith($this->mime_type, 'audio/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Resolve public or canonical delivery URL.
     */
    public function url(): string
    {
        /** @var DeliveryAdapter $delivery */
        $delivery = app(DeliveryAdapter::class);
        return $delivery->url($this);
    }

    /**
     * Resolve temporary expiring URL for private media.
     */
    public function temporaryUrl(int $minutes = 15): string
    {
        /** @var DeliveryAdapter $delivery */
        $delivery = app(DeliveryAdapter::class);
        return $delivery->temporaryUrl($this, $minutes);
    }

    /**
     * Resolve thumbnail URL for a given preset, falling back to original if image.
     */
    public function thumbnailUrl(string $preset = 'thumb'): string
    {
        $variant = $this->variants->firstWhere('preset', $preset);
        if ($variant && $variant->isReady()) {
            return $variant->url();
        }

        return $this->url();
    }

    /**
     * Human-readable formatted file size.
     */
    public function humanSize(): string
    {
        $bytes = $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', 'ready');
    }

    public function scopeInFolder(Builder $query, ?int $folderId): Builder
    {
        return $query->where('folder_id', $folderId);
    }

    public function scopeInCollection(Builder $query, ?string $collection): Builder
    {
        if (empty($collection)) {
            return $query;
        }

        return $query->where('collection', $collection);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('original_name', 'like', "%{$term}%")
              ->orWhere('extension', 'like', "%{$term}%")
              ->orWhere('mime_type', 'like', "%{$term}%");
        });
    }
}
