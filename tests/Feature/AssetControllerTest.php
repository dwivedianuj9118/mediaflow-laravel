<?php

namespace MediaFlow\Laravel\Tests\Feature;

use MediaFlow\Laravel\Tests\TestCase;

class AssetControllerTest extends TestCase
{
    public function test_vendor_css_asset_served_properly(): void
    {
        $response = $this->get('/vendor/mediaflow/css/mediaflow.css');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/css', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.mf-container', $response->getContent());
    }

    public function test_vendor_js_asset_served_properly(): void
    {
        $response = $this->get('/vendor/mediaflow/js/mediaflow.js');
        $response->assertStatus(200);
        $this->assertStringContainsString('javascript', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('window.MediaFlow', $response->getContent());
    }

    public function test_prefixed_asset_routes_work(): void
    {
        $cssResponse = $this->get(route('mediaflow.assets.css'));
        $cssResponse->assertStatus(200);

        $jsResponse = $this->get(route('mediaflow.assets.js'));
        $jsResponse->assertStatus(200);
    }
}
