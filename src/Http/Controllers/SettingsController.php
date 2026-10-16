<?php

namespace MediaFlow\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use MediaFlow\Laravel\Storage\CloudProviderManager;
use MediaFlow\Laravel\Support\MediaFlowSettingsManager;

class SettingsController extends Controller
{
    protected MediaFlowSettingsManager $settingsManager;
    protected CloudProviderManager $providerManager;

    /**
     * Settings keys accepted from the UI / REST API and how each is validated.
     * Anything not listed here is ignored, so arbitrary keys can never be stored.
     *
     * @var array<string, string>
     */
    protected array $allowed = [
        // images
        'images.default_format' => 'in:webp,jpeg,png,keep',
        'images.default_quality' => 'int:30:100',
        'images.max_width' => 'int:20:20000',
        'images.max_height' => 'int:20:20000',
        'images.auto_optimize' => 'bool',
        'images.strip_exif' => 'bool',
        // upload & storage
        'upload.max_file_size_kb' => 'int:1:10485760',
        'storage.default_disk' => 'disk',
        'storage_quota_mb' => 'int:0:100000000',
        // delivery
        'delivery.driver' => 'in:disk,cdn,signed',
        'delivery.base_url' => 'url',
        'delivery.signed_url_expiration_minutes' => 'int:1:10080',
        // ui
        'ui.theme' => 'in:system,indigo,emerald,violet,sky,amber,rose,slate,custom',
        'ui.mode' => 'in:system,light,dark',
        'ui.custom_primary' => 'color',
        'ui.folder_color' => 'color',
        'ui.inherit_system' => 'bool',
        'ui.animations' => 'bool',
        // access control (config/.env values remain a floor, see auth.allow_ui_override)
        'auth.enabled' => 'bool',
        'auth.guard' => 'slug',
        'auth.roles' => 'list',
        'auth.permissions' => 'list',
        'auth.gate' => 'slug',
        // cloud provider
        'provider.name' => 'provider',
        'provider.key' => 'string:512',
        'provider.secret' => 'secret',
        'provider.account_id' => 'string:255',
        'provider.bucket' => 'string:255',
        'provider.public_url' => 'url',
        'provider.region' => 'string:64',
        'provider.endpoint' => 'url',
        'provider.path_style' => 'bool',
    ];

    public function __construct(
        MediaFlowSettingsManager $settingsManager,
        CloudProviderManager $providerManager
    ) {
        $this->settingsManager = $settingsManager;
        $this->providerManager = $providerManager;
    }

    /**
     * Display settings page or return JSON settings (credentials are always masked).
     */
    public function index(Request $request)
    {
        $settings = $this->settingsManager->allForDisplay();
        $providers = $this->providerManager->getProviders();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'settings' => $settings,
                'providers' => $providers,
            ]);
        }

        return view('mediaflow::pages.settings', compact('settings', 'providers'));
    }

    /**
     * Update settings. Only whitelisted keys are stored, each validated by type.
     */
    public function update(Request $request)
    {
        $clean = $this->sanitizeInput($request->except(['_token', '_method']));

        foreach ($clean as $key => $val) {
            $this->settingsManager->set($key, $val);
        }

        // Apply updated cloud provider if applicable
        $providerSettings = $this->settingsManager->get('provider');
        if (!empty($providerSettings['name']) && $providerSettings['name'] !== 'local') {
            $this->providerManager->apply($providerSettings);
        }

        if ($request->wantsJson() || $request->ajax()) {
            $display = $this->settingsManager->allForDisplay();

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully.',
                'settings' => $display,
                'env_snippet' => $this->providerManager->generateEnvSnippet($display['provider'] ?? []),
            ]);
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Test connection to a specific cloud provider on the fly.
     */
    public function testProvider(Request $request): JsonResponse
    {
        $params = $request->only(['name', 'provider', 'key', 'secret', 'account_id', 'bucket', 'public_url', 'region', 'endpoint', 'path_style']);

        // The settings page only ever sees a masked secret; swap in the stored one.
        if (($params['secret'] ?? null) === MediaFlowSettingsManager::SECRET_MASK) {
            $params['secret'] = $this->settingsManager->get('provider.secret');
        }

        $result = $this->providerManager->testConnection($params);

        $snippetParams = $params;
        if (!empty($snippetParams['secret'])) {
            $snippetParams['secret'] = $request->input('secret') === MediaFlowSettingsManager::SECRET_MASK
                ? MediaFlowSettingsManager::SECRET_MASK
                : $snippetParams['secret'];
        }
        $result['env_snippet'] = $this->providerManager->generateEnvSnippet($snippetParams);

        return response()->json($result);
    }

    /**
     * Return list of 10 supported cloud providers.
     */
    public function providers(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'providers' => $this->providerManager->getProviders(),
        ]);
    }

    /**
     * Reset settings to defaults.
     */
    public function reset(): JsonResponse
    {
        $this->settingsManager->reset();

        return response()->json([
            'success' => true,
            'message' => 'Settings reset to system defaults.',
            'settings' => $this->settingsManager->allForDisplay(),
        ]);
    }

    /**
     * Normalise aliases, drop unknown keys and validate every value.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function sanitizeInput(array $input): array
    {
        $aliases = $this->aliases();
        $flat = [];

        foreach ($input as $key => $value) {
            // Nested arrays (e.g. {"images": {"default_quality": 80}}) are flattened to dot keys,
            // except list-type values such as auth.roles.
            if (is_array($value) && !array_is_list($value)) {
                foreach ($value as $subKey => $subValue) {
                    $flat["{$key}.{$subKey}"] = $subValue;
                }
                continue;
            }
            // PHP turns dots in form field names into underscores; accept both spellings.
            $flat[$key] = $value;
        }

        $clean = [];
        $errors = [];

        foreach ($flat as $key => $value) {
            $canonical = $aliases[$key] ?? $key;
            if (!isset($this->allowed[$canonical])) {
                $underscored = $this->underscoreMap()[$key] ?? null;
                if ($underscored === null) {
                    continue; // silently ignore unknown keys
                }
                $canonical = $underscored;
            }

            $rule = $this->allowed[$canonical];

            try {
                $normalized = $this->validateValue($canonical, $rule, $value);
            } catch (\InvalidArgumentException $e) {
                $errors[$key] = [$e->getMessage()];
                continue;
            }

            if ($normalized === '__skip__') {
                continue;
            }

            // Keep the alias key the caller used so existing lookups keep working.
            $storeKey = isset($aliases[$key]) ? $key : $canonical;
            $clean[$storeKey] = $normalized;
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $clean;
    }

    protected function validateValue(string $key, string $rule, $value)
    {
        [$type, $arg1, $arg2] = array_pad(explode(':', $rule, 3), 3, null);

        switch ($type) {
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'int':
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException("{$key} must be a number.");
                }
                $int = (int) $value;
                if ($int < (int) $arg1 || $int > (int) $arg2) {
                    throw new \InvalidArgumentException("{$key} must be between {$arg1} and {$arg2}.");
                }
                return $int;

            case 'in':
                $options = explode(',', $arg1 . ($arg2 !== null ? ':' . $arg2 : ''));
                if (!in_array((string) $value, $options, true)) {
                    throw new \InvalidArgumentException("{$key} must be one of: " . implode(', ', $options) . '.');
                }
                return (string) $value;

            case 'color':
                if ($value === null || $value === '') {
                    return null;
                }
                $color = MediaFlowSettingsManager::sanitizeColor($value);
                if ($color === null) {
                    throw new \InvalidArgumentException("{$key} must be a hex colour such as #4f46e5.");
                }
                return $color;

            case 'url':
                if ($value === null || $value === '') {
                    return null;
                }
                $value = trim((string) $value);
                $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                    throw new \InvalidArgumentException("{$key} must be a valid http(s) URL.");
                }
                return $value;

            case 'slug':
                if ($value === null || $value === '') {
                    return null;
                }
                if (!is_string($value) || !preg_match('/^[A-Za-z0-9_.:\-]{1,100}$/', $value)) {
                    throw new \InvalidArgumentException("{$key} may only contain letters, numbers, dashes, dots, colons and underscores.");
                }
                return $value;

            case 'list':
                $items = is_array($value) ? $value : explode(',', (string) $value);
                $items = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $items), fn ($v) => $v !== ''));
                foreach ($items as $item) {
                    if (!preg_match('/^[A-Za-z0-9_.:\- ]{1,100}$/', $item)) {
                        throw new \InvalidArgumentException("{$key} contains an invalid entry.");
                    }
                }
                return $items;

            case 'disk':
                if (!is_string($value) || !is_array(config("filesystems.disks.{$value}"))) {
                    throw new \InvalidArgumentException("{$key} must be a disk defined in config/filesystems.php.");
                }
                return $value;

            case 'provider':
                $value = (string) $value;
                if ($value !== 'local' && $this->providerManager->getProvider($value) === null) {
                    throw new \InvalidArgumentException("Unknown storage provider [{$value}].");
                }
                return $value;

            case 'secret':
                // A masked or empty secret means "keep the stored one".
                if ($value === null || $value === '' || $value === MediaFlowSettingsManager::SECRET_MASK) {
                    return '__skip__';
                }
                return mb_substr((string) $value, 0, 1024);

            case 'string':
                if ($value === null) {
                    return null;
                }
                if (!is_scalar($value)) {
                    throw new \InvalidArgumentException("{$key} must be text.");
                }
                return mb_substr(trim((string) $value), 0, (int) $arg1);
        }

        throw new \InvalidArgumentException("{$key} cannot be changed.");
    }

    /**
     * Short alias keys accepted by the API (mirrors MediaFlowSettingsManager aliases).
     *
     * @return array<string, string>
     */
    protected function aliases(): array
    {
        return [
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
            'provider_name' => 'provider.name',
            'provider_key' => 'provider.key',
            'provider_secret' => 'provider.secret',
            'provider_bucket' => 'provider.bucket',
            'provider_public_url' => 'provider.public_url',
            'provider_region' => 'provider.region',
            'provider_endpoint' => 'provider.endpoint',
            'provider_path_style' => 'provider.path_style',
        ];
    }

    /**
     * Map "images_default_format" (how PHP receives form field "images.default_format") to dot keys.
     *
     * @return array<string, string>
     */
    protected function underscoreMap(): array
    {
        $map = [];
        foreach (array_keys($this->allowed) as $key) {
            $map[str_replace('.', '_', $key)] = $key;
        }

        return $map;
    }
}
