<?php

namespace MediaFlow\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class MediaFlowSetting extends Model
{
    protected $table = 'mediaflow_settings';

    public function __construct(array $attributes = [])
    {
        $this->table = config('mediaflow.tables.settings', 'mediaflow_settings');
        parent::__construct($attributes);
    }

    public function getTable()
    {
        return config('mediaflow.tables.settings', parent::getTable() ?: 'mediaflow_settings');
    }

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public static function get(string $key, $default = null)
    {
        return app(\MediaFlow\Laravel\Support\MediaFlowSettingsManager::class)->get($key, $default);
    }

    public static function set(string $key, $value): void
    {
        app(\MediaFlow\Laravel\Support\MediaFlowSettingsManager::class)->set($key, $value);
    }
}

