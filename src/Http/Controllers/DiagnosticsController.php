<?php

namespace MediaFlow\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MediaFlow\Laravel\Facades\MediaFlow;

class DiagnosticsController extends Controller
{
    /**
     * Display the diagnostics and provider testing view.
     */
    public function view()
    {
        return view('mediaflow::pages.diagnostics');
    }

    /**
     * Return system diagnostics info.
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'gd_supported' => extension_loaded('gd'),
                'imagick_supported' => extension_loaded('imagick'),
                'webp_supported' => function_exists('imagewebp'),
                's3_driver_installed' => \MediaFlow\Laravel\Storage\CloudProviderManager::s3DriverInstalled(),
                'default_disk' => config('mediaflow.default_disk'),
                'temporary_disk' => config('mediaflow.temporary_disk'),
                'delivery_driver' => config('mediaflow.delivery.driver'),
                'max_file_size_kb' => config('mediaflow.max_file_size_kb'),
                'allowed_mimes_count' => count(config('mediaflow.allowed_mimes', [])),
                'queue_processing' => config('mediaflow.images.queue_processing'),
                'ui_enabled' => config('mediaflow.ui.enabled'),
            ],
        ]);
    }

    /**
     * Test storage provider connectivity safely.
     */
    public function testStorage(Request $request): JsonResponse
    {
        $disk = $request->input('disk') ?: config('mediaflow.default_disk');

        if (!is_string($disk) || !is_array(config("filesystems.disks.{$disk}"))) {
            return response()->json([
                'success' => false,
                'message' => 'Unknown storage disk. Use a disk defined in config/filesystems.php.',
            ], 422);
        }

        $startTime = microtime(true);
        $result = MediaFlow::testStorageConnection($disk);
        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        $result['latency_ms'] = $latencyMs;

        return response()->json($result);
    }
}
