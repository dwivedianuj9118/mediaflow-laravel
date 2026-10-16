<?php

namespace MediaFlow\Laravel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MediaFlow\Laravel\Models\Media;

class MediaFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Media $media;
    public string $errorMessage;

    public function __construct(Media $media, string $errorMessage)
    {
        $this->media = $media;
        $this->errorMessage = $errorMessage;
    }
}
