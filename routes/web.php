<?php

use Illuminate\Support\Facades\Route;
use MediaFlow\Laravel\Http\Controllers\AssetController;
use MediaFlow\Laravel\Http\Controllers\DeliveryController;
use MediaFlow\Laravel\Http\Controllers\DiagnosticsController;
use MediaFlow\Laravel\Http\Controllers\FolderController;
use MediaFlow\Laravel\Http\Controllers\MediaManagerController;
use MediaFlow\Laravel\Http\Controllers\SettingsController;
use MediaFlow\Laravel\Http\Controllers\UploadController;

// Direct Package Asset Serving (Zero-configuration fallback when vendor:publish has not been executed)
Route::get('vendor/mediaflow/css/mediaflow.css', [AssetController::class, 'css'])->name('mediaflow.asset.css');
Route::get('vendor/mediaflow/js/mediaflow.js', [AssetController::class, 'js'])->name('mediaflow.asset.js');
Route::get('vendor/mediaflow/img/{file}', [AssetController::class, 'img'])->name('mediaflow.asset.img');

$prefix = config('mediaflow.route_prefix', 'mediaflow');
$baseMiddleware = config('mediaflow.middleware', ['web']);
$customMiddleware = config('mediaflow.auth.custom_middleware', []);

$middleware = array_merge(
    (array) $baseMiddleware,
    [\MediaFlow\Laravel\Http\Middleware\AuthorizeMediaFlowAccess::class],
    (array) $customMiddleware
);

Route::group(['prefix' => $prefix, 'middleware' => $middleware, 'as' => 'mediaflow.'], function () {
    // Assets (prefixed aliases)
    Route::get('assets/css', [AssetController::class, 'css'])->name('assets.css');
    Route::get('assets/js', [AssetController::class, 'js'])->name('assets.js');
    Route::get('assets/img/{file}', [AssetController::class, 'img'])->name('assets.img');

    // Uploads
    Route::post('upload', [UploadController::class, 'upload'])->name('upload');
    Route::get('status/{id}', [UploadController::class, 'status'])->name('status');
    Route::get('csrf-token', [UploadController::class, 'csrfToken'])->name('csrf-token');

    // Manager and Files
    Route::get('/', [MediaManagerController::class, 'index']);
    Route::get('manager', [MediaManagerController::class, 'index'])->name('manager');
    Route::get('pipeline', [MediaManagerController::class, 'pipeline'])->name('pipeline');
    Route::get('docs', [MediaManagerController::class, 'docs'])->name('docs');
    Route::get('documentation', [MediaManagerController::class, 'docs'])->name('docs.alias');
    Route::get('media/{id}', [MediaManagerController::class, 'show'])->name('media.show');
    Route::patch('media/{id}', [MediaManagerController::class, 'update'])->name('media.update');
    Route::delete('media/{id}', [MediaManagerController::class, 'destroy'])->name('media.destroy');
    Route::post('media/{id}/optimize', [MediaManagerController::class, 'optimize'])->name('media.optimize');
    Route::post('media/bulk', [MediaManagerController::class, 'bulk'])->name('media.bulk');

    // Folders
    Route::get('folders', [FolderController::class, 'index'])->name('folders.index');
    Route::post('folders', [FolderController::class, 'store'])->name('folders.store');
    Route::patch('folders/{id}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('folders/{id}', [FolderController::class, 'destroy'])->name('folders.destroy');

    // Settings & Cloud Providers
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('settings-page', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/reset', [SettingsController::class, 'reset'])->name('settings.reset');
    Route::post('settings/test-provider', [SettingsController::class, 'testProvider'])->name('settings.test-provider');
    Route::get('settings/providers', [SettingsController::class, 'providers'])->name('settings.providers');

    // Diagnostics & Provider Testing
    Route::get('diagnostics', [DiagnosticsController::class, 'view'])->name('diagnostics.view');
    Route::get('diagnostics/info', [DiagnosticsController::class, 'info'])->name('diagnostics.info');
    Route::post('diagnostics/test', [DiagnosticsController::class, 'testStorage'])->name('diagnostics.test');

    // Secure Streaming for Signed/Private URLs
    Route::get('delivery/{media}', [DeliveryController::class, 'stream'])->name('delivery.stream');
});
