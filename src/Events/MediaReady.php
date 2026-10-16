<?php

namespace MediaFlow\Laravel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MediaFlow\Laravel\Models\Media;

class MediaReady
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Media $media;

    public function __construct(Media $media)
    {
        $this->media = $media;
    }
}
