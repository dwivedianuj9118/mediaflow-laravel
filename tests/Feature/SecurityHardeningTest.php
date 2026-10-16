<?php

namespace MediaFlow\Laravel\Tests\Feature;

use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use MediaFlow\Laravel\Events\MediaReady;
use MediaFlow\Laravel\Facades\MediaFlow;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaFlowSetting;
use MediaFlow\Laravel\Models\MediaFolder;
use MediaFlow\Laravel\Support\MediaFlowSettingsManager;
use MediaFlow\Laravel\Tests\TestCase;

class HardeningTestUser extends AuthenticatableUser
{
    protected $guarded = [];
}

class SecurityHardeningTest extends TestCase
{
    public function test_package_config_requires_authentication_by_default(): void
    {
        $config = require __DIR__ . '/../../config/mediaflow.php';

        $this->assertTrue((bool) $config['auth']['enabled']);
    }

    public function test_ui_setting_cannot_disable_auth_enforced_by_config(): void
    {
        config()->set('mediaflow.auth.enabled', true);
        MediaFlowSetting::set('auth.enabled', false);

        $this->getJson(route('mediaflow.manager'))->assertStatus(401);
    }

    public function test_ui_setting_can_disable_auth_when_override_is_allowed(): void
    {
        config()->set('mediaflow.auth.enabled', true);
        config()->set('mediaflow.auth.allow_ui_override', true);
        MediaFlowSetting::set('auth.enabled', false);

        $this->getJson(route('mediaflow.manager'))->assertStatus(200);
    }

    public function test_ui_roles_cannot_replace_config_roles(): void
    {
        config()->set('mediaflow.auth.enabled', true);
        config()->set('mediaflow.auth.roles', ['admin']);
        MediaFlowSetting::set('auth.roles', ['editor']);

        $editor = new HardeningTestUser(['id' => 10, 'name' => 'Eve', 'role' => 'editor']);
        $this->actingAs($editor)->getJson(route('mediaflow.manager'))->assertStatus(403);
    }

    public function test_settings_update_ignores_unknown_keys_and_rejects_bad_values(): void
    {
        $this->postJson(route('mediaflow.settings.update'), [
            'evil.key' => 'x',
            'theme' => 'emerald',
        ])->assertStatus(200);

        $this->assertDatabaseMissing('mediaflow_settings', ['key' => 'evil.key']);

        $this->postJson(route('mediaflow.settings.update'), [
            'folder_color' => 'red;}</style><script>alert(1)</script>',
        ])->assertStatus(422);

        $this->postJson(route('mediaflow.settings.update'), [
            'provider.endpoint' => 'file:///etc/passwd',
        ])->assertStatus(422);
    }

    public function test_css_variables_never_contain_unsafe_colour_values(): void
    {
        $manager = app(MediaFlowSettingsManager::class);
        // Simulate a value written directly to the database, bypassing validation.
        MediaFlowSetting::query()->create(['key' => 'ui.folder_color', 'value' => 'red;}</style><script>alert(1)</script>']);
        \Illuminate\Support\Facades\Cache::flush();

        $this->assertStringNotContainsString('<script>', $manager->getCssVariables());

        $this->get(route('mediaflow.manager'))
            ->assertStatus(200)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_provider_secret_is_masked_and_preserved(): void
    {
        $manager = app(MediaFlowSettingsManager::class);
        $manager->set('provider.secret', 'super-secret-value');

        $json = $this->getJson(route('mediaflow.settings'))->assertStatus(200);
        $this->assertSame(MediaFlowSettingsManager::SECRET_MASK, $json->json('settings.provider.secret'));
        $this->assertStringNotContainsString('super-secret-value', $json->getContent());

        $this->get(route('mediaflow.settings'))->assertDontSee('super-secret-value');

        // Saving the form with the masked placeholder keeps the stored secret.
        $this->postJson(route('mediaflow.settings.update'), [
            'provider.secret' => MediaFlowSettingsManager::SECRET_MASK,
            'provider.bucket' => 'media',
        ])->assertStatus(200)->assertDontSee('super-secret-value');

        $this->assertSame('super-secret-value', app(MediaFlowSettingsManager::class)->get('provider.secret'));
    }

    public function test_provider_test_rejects_metadata_endpoints(): void
    {
        $response = $this->postJson(route('mediaflow.settings.test-provider'), [
            'name' => 'minio',
            'key' => 'k',
            'secret' => 's',
            'bucket' => 'b',
            'endpoint' => 'http://169.254.169.254/latest/meta-data',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('not allowed', $response->json('message'));
    }

    public function test_client_extension_is_not_trusted_for_storage_key(): void
    {
        Event::fake([MediaReady::class]);

        // A real (non-fake) upload so the MIME type is sniffed from the contents.
        $path = tempnam(sys_get_temp_dir(), 'mf_txt_');
        file_put_contents($path, "just some plain notes\nsecond line\n");
        $file = new UploadedFile($path, 'photo.jpg', null, null, true);
        $media = MediaFlow::upload($file);

        $this->assertSame('text/plain', $media->mime_type);
        $this->assertStringEndsWith('.txt', $media->storage_key);
    }

    public function test_html_upload_is_blocked(): void
    {
        $file = UploadedFile::fake()->createWithContent('page.html', '<html><body><script>alert(1)</script></body></html>');

        $this->postJson(route('mediaflow.upload'), ['file' => $file])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_svg_with_event_handlers_is_rejected(): void
    {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><image href="x" onerror="alert(1)"/></svg>';
        $file = UploadedFile::fake()->createWithContent('logo.svg', $svg);

        $response = $this->postJson(route('mediaflow.upload'), ['file' => $file]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_exif_metadata_is_stripped_from_jpeg_uploads(): void
    {
        Event::fake([MediaReady::class]);

        $image = imagecreatetruecolor(40, 30);
        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = ob_get_clean();

        // Inject a minimal APP1/Exif segment right after the SOI marker.
        $exifPayload = "Exif\x00\x00" . "MM\x00\x2A\x00\x00\x00\x08\x00\x00";
        $segment = "\xFF\xE1" . pack('n', strlen($exifPayload) + 2) . $exifPayload;
        $withExif = substr($jpeg, 0, 2) . $segment . substr($jpeg, 2);
        $this->assertStringContainsString("Exif\x00\x00", $withExif);

        $media = MediaFlow::upload(UploadedFile::fake()->createWithContent('gps.jpg', $withExif));

        $stored = MediaFlow::disk($media->disk)->read($media->storage_key);
        $this->assertStringNotContainsString("Exif\x00\x00", $stored);
        $this->assertTrue($media->metadata['exif_stripped']);
    }

    public function test_private_media_with_variants_lists_and_streams_on_local_disk(): void
    {
        Event::fake([MediaReady::class]);

        $media = MediaFlow::upload(UploadedFile::fake()->image('secret.jpg', 300, 300), ['visibility' => 'private']);
        $this->assertGreaterThan(0, $media->variants()->count());

        $response = $this->getJson(route('mediaflow.manager'));
        $response->assertStatus(200);

        $thumbUrl = $response->json('media.0.thumbnail_url');
        $this->assertStringContainsString('signature=', $thumbUrl);
        $this->assertStringContainsString('variant=thumb', $thumbUrl);

        $stream = $this->get($thumbUrl);
        $stream->assertStatus(200);
        $this->assertSame('image/webp', $stream->headers->get('Content-Type'));
    }

    public function test_renaming_a_folder_updates_descendant_paths(): void
    {
        $parent = MediaFolder::create(['name' => 'Blog']);
        $child = MediaFolder::create(['name' => 'Covers', 'parent_id' => $parent->id]);
        $grandchild = MediaFolder::create(['name' => '2026', 'parent_id' => $child->id]);

        $this->assertSame('blog/covers/2026', $grandchild->fresh()->path);

        $this->patchJson(route('mediaflow.folders.update', $parent->id), ['name' => 'News'])
            ->assertStatus(200);

        $this->assertSame('news', $parent->fresh()->path);
        $this->assertSame('news/covers', $child->fresh()->path);
        $this->assertSame('news/covers/2026', $grandchild->fresh()->path);
    }

    public function test_user_scoping_hides_other_users_media(): void
    {
        Event::fake([MediaReady::class]);
        config()->set('mediaflow.scope.user_scoped', true);

        $alice = new HardeningTestUser(['id' => 1, 'name' => 'Alice']);
        $bob = new HardeningTestUser(['id' => 2, 'name' => 'Bob']);

        $this->actingAs($alice);
        $aliceMedia = MediaFlow::upload(UploadedFile::fake()->image('alice.jpg', 50, 50));

        $this->actingAs($bob);
        $this->getJson(route('mediaflow.manager'))->assertJsonPath('pagination.total', 0);
        $this->deleteJson(route('mediaflow.media.destroy', $aliceMedia->id))->assertStatus(404);

        $this->actingAs($alice);
        $this->getJson(route('mediaflow.manager'))->assertJsonPath('pagination.total', 1);
        $this->assertNotNull(Media::find($aliceMedia->id));
    }

    public function test_unknown_diagnostics_disk_is_rejected(): void
    {
        $this->postJson(route('mediaflow.diagnostics.test'), ['disk' => 'does-not-exist'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_missing_cached_config_falls_back_to_safe_package_defaults(): void
    {
        // Simulates `php artisan config:cache` run before MediaFlow was installed.
        // With cached config Laravel skips mergeConfigFrom(), so only the safeguard runs.
        config()->set('mediaflow', null);
        $provider = new \MediaFlow\Laravel\MediaFlowServiceProvider($this->app);
        (new \ReflectionMethod($provider, 'ensureConfigLoaded'))->invoke($provider);

        $this->assertNotEmpty(config('mediaflow.allowed_mimes'));
        $this->assertContains('html', config('mediaflow.blocked_extensions'));
        $this->assertNotEmpty(config('mediaflow.images.presets'));
    }

    public function test_provider_test_explains_missing_s3_driver(): void
    {
        if (\MediaFlow\Laravel\Storage\CloudProviderManager::s3DriverInstalled()) {
            $this->assertStringContainsString('flysystem-aws-s3-v3', \MediaFlow\Laravel\Storage\CloudProviderManager::missingDriverMessage());
            return;
        }

        $result = app(\MediaFlow\Laravel\Storage\CloudProviderManager::class)
            ->testConnection(['name' => 'aws', 'key' => 'k', 'secret' => 's', 'bucket' => 'b']);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('flysystem-aws-s3-v3', $result['message']);
    }
}
