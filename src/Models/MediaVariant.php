<?php

namespace MediaFlow\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MediaFlow\Laravel\Contracts\DeliveryAdapter;

class MediaVariant extends Model
{
    protected $table = 'mediaflow_variants';

    public function __construct(array $attributes = [])
    {
        $this->table = config('mediaflow.tables.variants', 'mediaflow_variants');
        parent::__construct($attributes);
    }

    public function getTable()
    {
        return config('mediaflow.tables.variants', parent::getTable() ?: 'mediaflow_variants');
    }

    protected $fillable = [
        'media_id',
        'preset',
        'disk',
        'storage_key',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'checksum',
        'status',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    /**
     * Resolve delivery URL for this variant.
     */
    public function url(): string
    {
        /** @var DeliveryAdapter $delivery */
        $delivery = app(DeliveryAdapter::class);
        return $delivery->variantUrl($this);
    }
}
