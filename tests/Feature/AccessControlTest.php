<?php

namespace MediaFlow\Laravel\Tests\Feature;

use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use MediaFlow\Laravel\Events\MediaReady;
use MediaFlow\Laravel\Facades\MediaFlow;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaFlowSetting;
use MediaFlow\Laravel\Tests\TestCase;

class TestUser extends AuthenticatableUser
{
    protected $guarded = [];
}

class AccessControlTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset authorization callback to null between tests
        MediaFlow::authorizeUsing(null);
        parent::tearDown();
    }

    public function test_guest_can_access_mediaflow_when_auth_is_disabled(): void
    {
        config()->set('mediaflow.auth.enabled', false);

        $response = $this->get(route('mediaflow.manager'));
        $response->assertStatus(200);
    }

    public function test_guest_blocked_when_auth_is_enabled_via_config(): void
    {
        config()->set('mediaflow.auth.enabled', true);

        $response = $this->getJson(route('mediaflow.manager'));
        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Unauthenticated. MediaFlow requires authentication.');
    }

    public function test_guest_blocked_when_auth_is_enabled_via_database_settings(): void
    {
        MediaFlowSetting::set('auth.enabled', true);

        $response = $this->getJson(route('mediaflow.manager'));
        $response->assertStatus(401);
    }

    public function test_authenticated_user_gains_access_when_auth_is_enabled(): void
    {
        config()->set('mediaflow.auth.enabled', true);

        $user = new TestUser(['id' => 1, 'name' => 'Alice']);
        $response = $this->actingAs($user)->get(route('mediaflow.manager'));

        $response->assertStatus(200);
    }

    public function test_authenticated_user_without_required_role_is_denied(): void
    {
        config()->set('mediaflow.auth.enabled', true);
        config()->set('mediaflow.auth.roles', ['admin', 'media_manager']);

        $user = new TestUser(['id' => 2, 'name' => 'Bob', 'role' => 'editor']);
        $response = $this->actingAs($user)->getJson(route('mediaflow.manager'));

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Required: admin, media_manager', $response->json('error'));
    }

    public function test_authenticated_user_with_required_role_is_granted_access(): void
    {
        config()->set('mediaflow.auth.enabled', true);
        config()->set('mediaflow.auth.roles', ['admin', 'media_manager']);

        $user = new TestUser(['id' => 3, 'name' => 'Charlie', 'role' => 'admin']);
        $response = $this->actingAs($user)->get(route('mediaflow.manager'));

        $response->assertStatus(200);
    }

    public function test_custom_authorization_callback_allows_or_denies_access(): void
    {
        // 1. Callback denies access
        MediaFlow::authorize(function ($user, $request) {
            return false;
        });

        $user = new TestUser(['id' => 4, 'name' => 'Dan']);
        $response = $this->actingAs($user)->getJson(route('mediaflow.manager'));

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);

        // 2. Callback allows access
        MediaFlow::authorize(function ($user, $request) {
            return true;
        });

        $response = $this->actingAs($user)->get(route('mediaflow.manager'));
        $response->assertStatus(200);
    }

    public function test_signed_delivery_stream_bypasses_auth_guard(): void
    {
        Event::fake([MediaReady::class]);

        config()->set('mediaflow.auth.enabled', true);
        config()->set('mediaflow.auth.roles', ['superadmin']);

        // Create media item
        $file = UploadedFile::fake()->image('private_doc.jpg', 400, 400);
        $media = MediaFlow::upload($file, ['visibility' => 'private']);

        // Generate signed URL
        $signedUrl = URL::temporarySignedRoute(
            'mediaflow.delivery.stream',
            now()->addMinutes(10),
            ['media' => $media->id]
        );

        // Access as unauthenticated guest: should bypass management auth guard and stream successfully!
        $response = $this->get($signedUrl);
        $response->assertStatus(200);
        $this->assertEquals($media->mime_type, $response->headers->get('Content-Type'));
    }
}
