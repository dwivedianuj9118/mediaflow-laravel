<?php

namespace MediaFlow\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UploadSession extends Model
{
    protected $table = 'mediaflow_upload_sessions';

    public function __construct(array $attributes = [])
    {
        $this->table = config('mediaflow.tables.upload_sessions', 'mediaflow_upload_sessions');
        parent::__construct($attributes);
    }

    public function getTable()
    {
        return config('mediaflow.tables.upload_sessions', parent::getTable() ?: 'mediaflow_upload_sessions');
    }

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'original_name',
        'size_bytes',
        'mime_type',
        'temp_disk',
        'temp_path',
        'status',
        'options',
        'expires_at',
    ];

    protected $casts = [
        'options' => 'array',
        'size_bytes' => 'integer',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (UploadSession $session) {
            if (empty($session->id)) {
                $session->id = (string) Str::uuid();
            }
            if (empty($session->expires_at)) {
                $session->expires_at = now()->addHours(6);
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
