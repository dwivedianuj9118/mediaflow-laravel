<?php

namespace MediaFlow\Laravel\Tests\Feature;

use MediaFlow\Laravel\Models\MediaFlowSetting;
use MediaFlow\Laravel\Support\MediaFlowSettingsManager;
use MediaFlow\Laravel\Tests\TestCase;

class SettingsTest extends TestCase
{
    public function test_settings_page_renders_successfully(): void
    {
        $response = $this->get(route('mediaflow.settings'));

        $response->assertStatus(200);
        $response->assertSee('Media Configuration');
        $response->assertSee('Image Compression');
        $response->assertSee('Theme Color Preset');
    }

    public function test_can_update_settings_via_json(): void
    {
        $payload = [
            'default_image_format' => 'webp',
            'image_quality' => 78,
            'auto_convert_webp' => true,
            'max_image_width' => 2000,
            'max_image_height' => 2000,
            'theme' => 'emerald',
            'dark_mode' => 'dark',
            'accent_color' => '#10b981',
            'storage_quota_mb' => 2500,
        ];

        $response = $this->postJson(route('mediaflow.settings.update'), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $manager = app(MediaFlowSettingsManager::class);
        $this->assertEquals('webp', $manager->get('default_image_format'));
        $this->assertEquals(78, $manager->get('image_quality'));
        $this->assertEquals('emerald', $manager->get('theme'));
        $this->assertEquals('dark', $manager->get('dark_mode'));
        $this->assertEquals('dark', $manager->get('ui.mode'));
        $this->assertEquals(2500, $manager->get('storage_quota_mb'));
    }

    public function test_can_update_settings_via_standard_form_redirect(): void
    {
        $response = $this->post(route('mediaflow.settings.update'), [
            'theme' => 'violet',
            'image_quality' => 90,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('mediaflow_settings', [
            'key' => 'theme',
        ]);
        $this->assertEquals('violet', app(MediaFlowSettingsManager::class)->get('theme'));
    }

    public function test_can_reset_settings_to_defaults(): void
    {
        MediaFlowSetting::set('theme', 'rose');
        MediaFlowSetting::set('image_quality', 45);

        $this->assertEquals('rose', app(MediaFlowSettingsManager::class)->get('theme'));

        $response = $this->postJson(route('mediaflow.settings.reset'));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // After reset, theme should revert to default ('system')
        $this->assertEquals('system', app(MediaFlowSettingsManager::class)->get('theme'));
        $this->assertEquals(82, app(MediaFlowSettingsManager::class)->get('image_quality'));
    }

    public function test_settings_manager_falls_back_to_config_and_defaults(): void
    {
        $manager = app(MediaFlowSettingsManager::class);

        // Unset key should fall back to config or hardcoded default
        $this->assertEquals(82, $manager->get('image_quality'));
        $this->assertEquals('system', $manager->get('theme'));
        $this->assertEquals('webp', $manager->get('default_image_format'));
        $this->assertEquals('system', $manager->get('dark_mode'));
        $this->assertEquals('#f59e0b', $manager->get('folder_color'));
    }

    public function test_ui_theming_and_folder_color_helpers(): void
    {
        $manager = app(MediaFlowSettingsManager::class);

        $manager->set('theme', 'custom');
        $manager->set('accent_color', '#6366f1');
        $manager->set('folder_color', '#3b82f6');
        $manager->set('dark_mode', 'dark');

        $this->assertEquals('#6366f1', $manager->get('accent_color'));
        $this->assertEquals('#3b82f6', $manager->get('folder_color'));
        $this->assertEquals('dark', $manager->get('dark_mode'));

        $css = $manager->getCssVariables();
        $this->assertStringContainsString('--mf-primary: #6366f1;', $css);
        $this->assertStringContainsString('--mf-folder-color: #3b82f6;', $css);

        $attrs = $manager->getThemeAttributes();
        $this->assertStringContainsString('data-mf-mode="dark"', $attrs);

        $response = $this->get(route('mediaflow.settings'));
        $response->assertStatus(200);
        $response->assertSee('Color Mode (Light / Dark)');
        $response->assertSee('Media Folder Color');
        $response->assertSee('Live Real-time Component Preview');
    }
}
