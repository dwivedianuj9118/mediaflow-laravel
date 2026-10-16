<?php

namespace MediaFlow\Laravel\Tests\Feature;

use MediaFlow\Laravel\Models\MediaFlowSetting;
use MediaFlow\Laravel\Tests\TestCase;

class DocsPageTest extends TestCase
{
    public function test_it_renders_the_documentation_page_successfully()
    {
        $response = $this->get('/mediaflow/docs');

        $response->assertStatus(200);
        $response->assertSee('MediaFlow for Laravel Documentation');
        $response->assertSee('composer require dwivedianuj9118/mediaflow-laravel');
        $response->assertSee('&lt;x-mediaflow-input', false);
        $response->assertSee('MediaFlow::', false);
        $response->assertSee('5-Stage Pipeline Engine');
        $response->assertSee('compact');
    }

    public function test_it_resolves_named_route_mediaflow_docs()
    {
        $url = route('mediaflow.docs');
        $this->assertStringContainsString('/mediaflow/docs', $url);

        $response = $this->get($url);
        $response->assertStatus(200);
    }

    public function test_documentation_page_is_publicly_accessible_even_when_auth_is_enforced()
    {
        // Enforce auth
        MediaFlowSetting::set('auth.enabled', 1);
        MediaFlowSetting::set('auth.guard', 'web');

        // Manager should be protected (redirect to login or return 401 unauthenticated)
        $managerResponse = $this->get('/mediaflow/manager');
        $this->assertTrue(in_array($managerResponse->getStatusCode(), [401, 302], true));

        // Docs page MUST remain publicly accessible
        $docsResponse = $this->get('/mediaflow/docs');
        $docsResponse->assertStatus(200);
        $docsResponse->assertSee('Step-by-Step Installation');
    }

    public function test_it_supports_documentation_alias_route()
    {
        $response = $this->get('/mediaflow/documentation');
        $response->assertStatus(200);
        $response->assertSee('MediaFlow for Laravel Documentation');
    }
}
