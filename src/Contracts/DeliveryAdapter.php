<?php

namespace MediaFlow\Laravel\Contracts;

use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaVariant;

interface DeliveryAdapter
{
    /**
     * Resolve the public or canonical delivery URL for a media item.
     */
    public function url(Media $media): string;

    /**
     * Resolve delivery URL for a specific variant.
     */
    public function variantUrl(MediaVariant $variant): string;

    /**
     * Resolve a time-limited signed URL for private or restricted media.
     */
    public function temporaryUrl(Media $media, int $minutes = 15): string;
}
