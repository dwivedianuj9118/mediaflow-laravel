<?php

namespace MediaFlow\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use MediaFlow\Laravel\Console\Commands\CleanTemporaryUploadsCommand;
use MediaFlow\Laravel\Console\Commands\VerifyMediaStorageCommand;
use MediaFlow\Laravel\Contracts\DeliveryAdapter;
use MediaFlow\Laravel\Contracts\ImageProcessor;
use MediaFlow\Laravel\Delivery\DeliveryUrlResolver;
use MediaFlow\Laravel\Processing\GdImageProcessor;
use MediaFlow\Laravel\Storage\StorageAdapterRegistry;
use MediaFlow\Laravel\Upload\TemporaryUploadManager;
use MediaFlow\Laravel\Upload\UploadManager;
use MediaFlow\Laravel\Upload\UploadPipeline;
use MediaFlow\Laravel\Upload\ValidationService;

class MediaFlowServiceProvider extends ServiceProvider
{
    /**
     * Register package services and singletons.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mediaflow.php', 'mediaflow');

        // mergeConfigFrom() is skipped while the app's configuration is cached. If the
        // cache was built before MediaFlow was installed/updated, the "mediaflow" key is
        // missing and every limit (allowed MIME types, blocked extensions, presets...)
        // would silently fall back to "allow anything". Load the package defaults instead.
        $this->ensureConfigLoaded();

        // Storage and delivery singletons
        $this->app->singleton(StorageAdapterRegistry::class, function () {
            return new StorageAdapterRegistry();
        });

        $this->app->singleton(DeliveryAdapter::class, function ($app) {
            return new DeliveryUrlResolver($app->make(StorageAdapterRegistry::class));
        });

        $this->app->singleton(ImageProcessor::class, function () {
            return new GdImageProcessor();
        });

        // Cloud provider manager
        $this->app->singleton(\MediaFlow\Laravel\Storage\CloudProviderManager::class, function () {
            return new \MediaFlow\Laravel\Storage\CloudProviderManager();
        });

        // Upload and validation services
        $this->app->singleton(TemporaryUploadManager::class);
        $this->app->singleton(ValidationService::class);
        $this->app->singleton(UploadPipeline::class);

        // Core facade accessor
        $this->app->singleton('mediaflow', function ($app) {
            return new UploadManager(
                $app->make(UploadPipeline::class),
                $app->make(StorageAdapterRegistry::class),
                $app->make(DeliveryAdapter::class)
            );
        });

        $this->app->alias('mediaflow', UploadManager::class);
    }

    protected function ensureConfigLoaded(): void
    {
        $config = $this->app['config'];
        $current = $config->get('mediaflow');

        if (is_array($current) && array_key_exists('allowed_mimes', $current) && array_key_exists('blocked_extensions', $current)) {
            return;
        }

        $defaults = require __DIR__ . '/../config/mediaflow.php';
        $config->set('mediaflow', array_replace_recursive($defaults, is_array($current) ? $current : []));

        $this->app->booted(function () {
            try {
                // Warn at most once per hour to keep logs readable.
                if (!\Illuminate\Support\Facades\Cache::add('mediaflow:missing-config-warning', true, 3600)) {
                    return;
                }
                logger()->warning('MediaFlow: config "mediaflow" was missing from the cached configuration; package defaults were loaded. Run "php artisan config:cache" again after installing or updating MediaFlow.');
            } catch (\Throwable $e) {
                // logging unavailable
            }
        });
    }

    /**
     * Bootstrap package resources.
     */
    public function boot(): void
    {
        // 1. Config publishing
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/mediaflow.php' => config_path('mediaflow.php'),
            ], 'mediaflow-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'mediaflow-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/mediaflow'),
            ], 'mediaflow-views');

            $this->publishes([
                __DIR__ . '/../resources/css' => public_path('vendor/mediaflow/css'),
                __DIR__ . '/../resources/js' => public_path('vendor/mediaflow/js'),
                __DIR__ . '/../resources/img' => public_path('vendor/mediaflow/img'),
            ], 'mediaflow-assets');

            $this->commands([
                CleanTemporaryUploadsCommand::class,
                VerifyMediaStorageCommand::class,
            ]);
        }

        // 2. Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 3. Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // 4. Load views & register Blade components
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mediaflow');

        Blade::component('mediaflow::components.uploader', 'mediaflow::uploader');
        Blade::component('mediaflow::components.uploader', 'mediaflow-uploader');
        Blade::component('mediaflow::components.manager', 'mediaflow::manager');
        Blade::component('mediaflow::components.manager', 'mediaflow-manager');
        Blade::component('mediaflow::components.input', 'mediaflow::input');
        Blade::component('mediaflow::components.input', 'mediaflow-input');

        Blade::directive('mediaflowStyles', function () {
            return "<?php if(!defined('MEDIAFLOW_STYLES_LOADED')): define('MEDIAFLOW_STYLES_LOADED', true); echo '<link rel=\"stylesheet\" href=\"' . asset('vendor/mediaflow/css/mediaflow.css') . '\">'; endif; ?>";
        });

        Blade::directive('mediaflowScripts', function () {
            return "<?php if(!defined('MEDIAFLOW_SCRIPTS_LOADED')): define('MEDIAFLOW_SCRIPTS_LOADED', true); echo '<script src=\"' . asset('vendor/mediaflow/js/mediaflow.js') . '\" defer></script>'; endif; ?>";
        });

        // 5. Register middleware alias
        if (isset($this->app['router'])) {
            $this->app['router']->aliasMiddleware('mediaflow.auth', \MediaFlow\Laravel\Http\Middleware\AuthorizeMediaFlowAccess::class);
        }

        // 6. Apply UI-saved storage disk, then cloud provider configuration if configured
        try {
            $settingsManager = $this->app->make(\MediaFlow\Laravel\Support\MediaFlowSettingsManager::class);

            $savedDisk = $settingsManager->getPersisted('storage.default_disk');
            if (is_string($savedDisk) && is_array(config("filesystems.disks.{$savedDisk}"))) {
                config(['mediaflow.default_disk' => $savedDisk]);
            }

            $providerSettings = $settingsManager->get('provider', config('mediaflow.provider', []));
            if (!empty($providerSettings['name']) && $providerSettings['name'] !== 'local') {
                $this->app->make(\MediaFlow\Laravel\Storage\CloudProviderManager::class)->apply($providerSettings);
            }
        } catch (\Throwable $e) {
            // Fail safely if database or config not ready
        }
    }
}
