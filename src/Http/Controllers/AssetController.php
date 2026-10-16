<?php

namespace MediaFlow\Laravel\Http\Controllers;

use Illuminate\Http\Response;

class AssetController
{
    /**
     * Serve package CSS asset dynamically when not yet published to public directory.
     */
    public function css(): Response
    {
        $path = __DIR__ . '/../../../resources/css/mediaflow.css';
        if (!file_exists($path)) {
            abort(404, 'MediaFlow stylesheet not found');
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * Serve package JS asset dynamically when not yet published to public directory.
     */
    public function js(): Response
    {
        $path = __DIR__ . '/../../../resources/js/mediaflow.js';
        if (!file_exists($path)) {
            abort(404, 'MediaFlow script not found');
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * Serve package image or diagram dynamically when not yet published to public directory.
     */
    public function img(string $file): Response
    {
        $safeFile = basename($file);
        $path = __DIR__ . '/../../../resources/img/' . $safeFile;
        if (!file_exists($path)) {
            abort(404, 'MediaFlow asset image not found');
        }

        $mimes = [
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];

        $ext = strtolower(pathinfo($safeFile, PATHINFO_EXTENSION));
        $contentType = $mimes[$ext] ?? 'application/octet-stream';

        return response(file_get_contents($path), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
