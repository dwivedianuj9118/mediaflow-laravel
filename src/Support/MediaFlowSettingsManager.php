<?php

namespace MediaFlow\Laravel\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use MediaFlow\Laravel\Models\MediaFlowSetting;

class MediaFlowSettingsManager
{
    protected const CACHE_KEY = 'mediaflow_settings_cache';

    /**
     * Get default settings merged from config.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'images' => [
                'default_format' => 'webp',
                'default_quality' => 82,
                'auto_optimize' => true,
                'strip_exif' => true,
                'max_width' => 2560,
                'max_height' => 2560,
                'driver' => config('mediaflow.images.driver', 'gd'),
                'presets' => config('mediaflow.images.presets', [
                    'thumb' => ['width' => 200, 'height' => 200, 'fit' => 'crop', 'format' => 'webp', 'quality' => 80],
                    'medium' => ['width' => 800, 'height' => 800, 'fit' => 'contain', 'format' => 'webp', 'quality' => 85],
                    'large' => ['width' => 1600, 'height' => 1600, 'fit' => 'contain', 'format' => 'webp', 'quality' => 85],
                ]),
            ],
            'upload' => [
                'max_file_size_kb' => config('mediaflow.max_file_size_kb', 20480),
                'allowed_mimes' => config('mediaflow.allowed_mimes', []),
            ],
            'storage' => [
                'default_disk' => config('mediaflow.default_disk', 'public'),
                'temporary_disk' => config('mediaflow.temporary_disk', 'local'),
            ],
            'delivery' => [
                'driver' => config('mediaflow.delivery.driver', 'disk'),
                'base_url' => config('mediaflow.delivery.base_url', null),
                'signed_url_expiration_minutes' => config('mediaflow.delivery.signed_url_expiration_minutes', 30),
            ],
            'ui' => [
                'theme' => config('mediaflow.ui.theme', 'system'),
                'mode' => config('mediaflow.ui.mode', 'system'),
                'custom_primary' => config('mediaflow.ui.custom_primary', '#4f46e5'),
                'folder_color' => config('mediaflow.ui.folder_color', '#f59e0b'),
                'inherit_system' => config('mediaflow.ui.inherit_system', true),
                'animations' => config('mediaflow.ui.animations', true),
                'items_per_page' => 24,
            ],
            'auth' => [
                'enabled' => config('mediaflow.auth.enabled', false),
                'guard' => config('mediaflow.auth.guard', null),
                'roles' => config('mediaflow.auth.roles', []),
                'permissions' => config('mediaflow.auth.permissions', []),
                'gate' => config('mediaflow.auth.gate', null),
                'custom_middleware' => config('mediaflow.auth.custom_middleware', []),
            ],
            'provider' => [
                'name' => config('mediaflow.provider.name', 'local'),
                'key' => config('mediaflow.provider.key', null),
                'secret' => config('mediaflow.provider.secret', null),
                'account_id' => config('mediaflow.provider.account_id', null),
                'bucket' => config('mediaflow.provider.bucket', null),
                'public_url' => config('mediaflow.provider.public_url', null),
                'region' => config('mediaflow.provider.region', null),
                'endpoint' => config('mediaflow.provider.endpoint', null),
                'path_style' => config('mediaflow.provider.path_style', null),
            ],
        ];
    }

    /**
     * Retrieve all settings, merging database overrides onto defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $defaults = $this->defaults();

        if (!$this->hasTable()) {
            return $defaults;
        }

        $persisted = $this->persisted();

        foreach ($persisted as $key => $val) {
            data_set($defaults, $key, $val);
        }

        return $defaults;
    }

    protected array $aliases = [
        'theme' => 'ui.theme',
        'mode' => 'ui.mode',
        'color_mode' => 'ui.mode',
        'dark_mode' => 'ui.mode',
        'folder_color' => 'ui.folder_color',
        'image_quality' => 'images.default_quality',
        'default_image_format' => 'images.default_format',
        'auto_convert_webp' => 'images.auto_optimize',
        'max_image_width' => 'images.max_width',
        'max_image_height' => 'images.max_height',
        'accent_color' => 'ui.custom_primary',
        'auth_enabled' => 'auth.enabled',
        'auth_guard' => 'auth.guard',
        'auth_roles' => 'auth.roles',
        'auth_permissions' => 'auth.permissions',
        'auth_gate' => 'auth.gate',
        'custom_middleware' => 'auth.custom_middleware',
        'provider_name' => 'provider.name',
        'provider_key' => 'provider.key',
        'provider_secret' => 'provider.secret',
        'provider_account_id' => 'provider.account_id',
        'provider_bucket' => 'provider.bucket',
        'provider_public_url' => 'provider.public_url',
        'provider_region' => 'provider.region',
        'provider_endpoint' => 'provider.endpoint',
        'provider_path_style' => 'provider.path_style',
    ];

    /**
     * Placeholder returned to the browser instead of stored secrets.
     */
    public const SECRET_MASK = '********';

    /**
     * Settings keys that hold credentials and must never be sent to the browser.
     *
     * @var array<int, string>
     */
    protected array $secretKeys = ['provider.secret', 'provider_secret'];

    /**
     * Get the raw value saved from the UI (database) for a key, or null when the
     * key was never saved. Defaults from config are NOT merged in.
     */
    public function getPersisted(string $key)
    {
        if (!$this->hasTable()) {
            return null;
        }

        $persisted = $this->persisted();
        $candidates = [$key];
        if (isset($this->aliases[$key])) {
            $candidates[] = $this->aliases[$key];
        }
        $flipped = array_flip($this->aliases);
        if (isset($flipped[$key])) {
            $candidates[] = $flipped[$key];
        }

        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $persisted) && $persisted[$candidate] !== null) {
                return $persisted[$candidate];
            }
        }

        return null;
    }

    /**
     * All settings with credentials masked, safe to render in HTML or JSON.
     *
     * @return array<string, mixed>
     */
    public function allForDisplay(): array
    {
        $all = $this->all();
        $secret = data_get($all, 'provider.secret');
        data_set($all, 'provider.secret', !empty($secret) ? self::SECRET_MASK : null);
        if (array_key_exists('provider_secret', $all)) {
            $all['provider_secret'] = !empty($all['provider_secret']) ? self::SECRET_MASK : null;
        }

        return $all;
    }

    /**
     * Raw persisted key/value pairs (cached).
     *
     * @return array<string, mixed>
     */
    protected function persisted(): array
    {
        $load = fn () => MediaFlowSetting::pluck('value', 'key')->toArray();

        try {
            return Cache::remember(self::CACHE_KEY, 3600, $load);
        } catch (\Throwable $e) {
            // A misconfigured cache store must not take MediaFlow down: read directly.
            return $load();
        }
    }

    protected function forgetCache(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // cache store unavailable; persisted() falls back to the database
        }
    }

    /**
     * Get a specific setting by dot notation or alias.
     */
    public function get(string $key, $default = null)
    {
        $all = $this->all();
        $val = data_get($all, $key);
        if ($val !== null) {
            return $val;
        }

        if (isset($this->aliases[$key])) {
            $aliasVal = data_get($all, $this->aliases[$key]);
            if ($aliasVal !== null) {
                return $aliasVal;
            }
        }

        $flipped = array_flip($this->aliases);
        if (isset($flipped[$key])) {
            $aliasVal = data_get($all, $flipped[$key]);
            if ($aliasVal !== null) {
                return $aliasVal;
            }
        }

        return $default;
    }

    /**
     * Save settings to database.
     */
    public function set(string $key, $value): void
    {
        if (!$this->hasTable()) {
            return;
        }

        MediaFlowSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        if (isset($this->aliases[$key])) {
            MediaFlowSetting::updateOrCreate(
                ['key' => $this->aliases[$key]],
                ['value' => $value]
            );
        }

        $this->forgetCache();
    }

    /**
     * Save multiple settings simultaneously.
     */
    public function setMany(array $settings): void
    {
        if (!$this->hasTable()) {
            return;
        }

        foreach ($settings as $key => $val) {
            $this->set($key, $val);
        }

        $this->forgetCache();
    }

    /**
     * Reset all persisted settings back to defaults.
     */
    public function reset(): void
    {
        if ($this->hasTable()) {
            MediaFlowSetting::truncate();
            $this->forgetCache();
        }
    }

    protected function hasTable(): bool
    {
        try {
            $tableName = config('mediaflow.tables.settings', config('mediaflow.table_prefix', 'mediaflow_') . 'settings');
            return Schema::hasTable($tableName);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get CSS variable overrides based on active UI settings.
     */
    public function getCssVariables(): string
    {
        $ui = $this->get('ui', []);
        $styles = [];

        $primary = static::sanitizeColor($ui['custom_primary'] ?? null);
        if ($primary && ($ui['theme'] ?? '') === 'custom') {
            $styles[] = "--mf-primary: {$primary};";
            $styles[] = "--mf-primary-hover: {$primary};";
        }

        $folderColor = static::sanitizeColor($ui['folder_color'] ?? null);
        if ($folderColor) {
            $styles[] = "--mf-folder-color: {$folderColor};";
        }

        return implode(' ', $styles);
    }

    /**
     * Return the colour only if it is a plain hex value (#rgb, #rrggbb, #rrggbbaa).
     * Anything else is dropped so stored settings can never inject CSS/HTML.
     */
    public static function sanitizeColor($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) ? $value : null;
    }

    /**
     * Get HTML attributes for active theme and color mode.
     */
    public function getThemeAttributes(): string
    {
        $ui = $this->get('ui', []);
        $attrs = [];

        if (!empty($ui['theme']) && $ui['theme'] !== 'system' && $ui['theme'] !== 'custom') {
            $attrs[] = 'data-mf-theme="' . e($ui['theme']) . '"';
        }

        if (!empty($ui['mode']) && $ui['mode'] !== 'system') {
            $attrs[] = 'data-mf-mode="' . e($ui['mode']) . '"';
        }

        return implode(' ', $attrs);
    }
}
