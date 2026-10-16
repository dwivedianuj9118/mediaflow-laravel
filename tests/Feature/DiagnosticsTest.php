<?php

namespace MediaFlow\Laravel\Tests\Feature;

use MediaFlow\Laravel\Tests\TestCase;

class DiagnosticsTest extends TestCase
{
    public function test_can_retrieve_diagnostics_info(): void
    {
        $response = $this->getJson(route('mediaflow.diagnostics.info'));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'environment' => [
                'php_version',
                'laravel_version',
                'gd_supported',
                'default_disk',
            ],
        ]);
    }

    public function test_can_test_storage_connection(): void
    {
        $response = $this->postJson(route('mediaflow.diagnostics.test'), [
            'disk' => 'public',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'message',
            'latency_ms',
            'details' => ['disk', 'writable', 'readable'],
        ]);
    }
}
