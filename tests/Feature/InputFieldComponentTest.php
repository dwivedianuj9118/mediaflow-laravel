<?php

namespace MediaFlow\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Tests\TestCase;

class InputFieldComponentTest extends TestCase
{
    public function test_shortcut_input_component_renders_default_empty_state(): void
    {
        $rendered = Blade::render('<x-mediaflow-input name="cover_image" label="Cover Image" help="Choose an image" />');

        $this->assertStringContainsString('name="cover_image"', $rendered);
        $this->assertStringContainsString('Cover Image', $rendered);
        $this->assertStringContainsString('Choose an image', $rendered);
        $this->assertStringContainsString('mf-input-dropzone', $rendered);
        $this->assertStringContainsString('Browse Media Library', $rendered);
        $this->assertStringContainsString('data-has-value="false"', $rendered);
    }

    public function test_shortcut_input_component_with_compact_mode_for_side_panels(): void
    {
        $rendered = Blade::render('<x-mediaflow-input name="sidebar_thumb" label="Sidebar Thumb" :compact="true" />');

        $this->assertStringContainsString('mf-input-compact', $rendered);
        $this->assertStringContainsString('name="sidebar_thumb"', $rendered);
    }

    public function test_shortcut_input_component_with_existing_media_model_value(): void
    {
        $media = Media::create([
            'uuid' => '98765432-1234-5678-1234-567812345678',
            'disk' => 'public',
            'storage_key' => 'media/2026/10/avatar.webp',
            'original_name' => 'my-avatar.png',
            'mime_type' => 'image/webp',
            'size_bytes' => 12345,
            'status' => 'ready',
        ]);

        $rendered = Blade::render('<x-mediaflow-input name="avatar" label="Avatar" :value="$media" />', [
            'media' => $media,
        ]);

        $this->assertStringContainsString('value="' . $media->id . '"', $rendered);
        $this->assertStringContainsString('my-avatar.png', $rendered);
        $this->assertStringContainsString('data-has-value="true"', $rendered);
        $this->assertStringContainsString('mf-input-preview-card', $rendered);
        $this->assertStringContainsString('Replace', $rendered);
    }

    public function test_both_component_tag_aliases_work_identically(): void
    {
        $rendered1 = Blade::render('<x-mediaflow-input name="test1" label="Test 1" />');
        $rendered2 = Blade::render('<x-mediaflow::input name="test1" label="Test 1" />');

        $this->assertStringContainsString('name="test1"', $rendered1);
        $this->assertStringContainsString('name="test1"', $rendered2);
    }

    public function test_shortcut_input_component_with_pipeline_visualizer_attribute(): void
    {
        $renderedDefault = Blade::render('<x-mediaflow-input name="cover_image" />');
        $this->assertStringContainsString('data-pipeline="true"', $renderedDefault);

        $rendered = Blade::render('<x-mediaflow-input name="cover_image" :pipeline="true" />');
        $this->assertStringContainsString('data-pipeline="true"', $rendered);

        $renderedDisabled = Blade::render('<x-mediaflow-input name="cover_image" :pipeline="false" />');
        $this->assertStringNotContainsString('data-pipeline="true"', $renderedDisabled);

        $renderedVisualizer = Blade::render('<x-mediaflow-input name="cover_image" :visualizer="true" />');
        $this->assertStringContainsString('data-pipeline="true"', $renderedVisualizer);
    }

    public function test_shortcut_input_component_renders_view_and_copy_buttons(): void
    {
        $media = Media::create([
            'uuid' => '33333333-3333-3333-3333-333333333333',
            'disk' => 'public',
            'storage_key' => 'media/2026/10/preview.png',
            'original_name' => 'preview.png',
            'mime_type' => 'image/png',
            'size_bytes' => 5000,
            'status' => 'ready',
        ]);

        $rendered = Blade::render('<x-mediaflow-input name="cover" :value="$media" />', ['media' => $media]);

        $this->assertStringContainsString('mf-input-view-btn', $rendered);
        $this->assertStringContainsString('mf-input-copy-btn', $rendered);
        $this->assertStringContainsString('Open', $rendered);
        $this->assertStringContainsString('Copy URL', $rendered);
    }

    public function test_shortcut_input_component_frontend_isolation_and_upload_only(): void
    {
        // 1. Upload only mode / browse=false / picker=false hides picker button
        $renderedUploadOnly = Blade::render('<x-mediaflow-input name="avatar" :upload-only="true" />');
        $this->assertStringNotContainsString('Browse Media Library', $renderedUploadOnly);
        $this->assertStringNotContainsString('Browse Your Media', $renderedUploadOnly);

        $renderedBrowseDisabled = Blade::render('<x-mediaflow-input name="avatar" :browse="false" />');
        $this->assertStringNotContainsString('Browse Media Library', $renderedBrowseDisabled);
        $this->assertStringNotContainsString('Browse Your Media', $renderedBrowseDisabled);

        $renderedHideBrowse = Blade::render('<x-mediaflow-input name="avatar" hide-browse />');
        $this->assertStringNotContainsString('Browse Media Library', $renderedHideBrowse);
        $this->assertStringNotContainsString('Browse Your Media', $renderedHideBrowse);

        $renderedPickerDisabled = Blade::render('<x-mediaflow-input name="avatar" :picker="false" />');
        $this->assertStringNotContainsString('Browse Media Library', $renderedPickerDisabled);
        $this->assertStringNotContainsString('Browse Your Media', $renderedPickerDisabled);

        // 2. Frontend mode sets data-isolated and custom text
        $renderedFrontend = Blade::render('<x-mediaflow-input name="avatar" :frontend="true" collection="user_avatars" />');
        $this->assertStringContainsString('data-isolated="true"', $renderedFrontend);
        $this->assertStringContainsString('data-picker-scope="frontend"', $renderedFrontend);
        $this->assertStringContainsString('Browse Your Media', $renderedFrontend);
    }

    public function test_shortcut_input_component_with_dynamic_file_types(): void
    {
        // Video preset
        $renderedVideo = Blade::render('<x-mediaflow-input name="promo_video" type="video" />');
        $this->assertStringContainsString('data-type="video"', $renderedVideo);
        $this->assertStringContainsString('video/*', $renderedVideo);
        $this->assertStringContainsString('MP4, WebM, OGG video', $renderedVideo);

        // Document / PDF preset
        $renderedDoc = Blade::render('<x-mediaflow-input name="user_contract" type="pdf" />');
        $this->assertStringContainsString('data-type="pdf"', $renderedDoc);
        $this->assertStringContainsString('.pdf', $renderedDoc);
        $this->assertStringContainsString('PDF, Word, Excel, CSV, text', $renderedDoc);

        // Audio preset
        $renderedAudio = Blade::render('<x-mediaflow-input name="podcast" type="audio" />');
        $this->assertStringContainsString('data-type="audio"', $renderedAudio);
        $this->assertStringContainsString('audio/*', $renderedAudio);
        $this->assertStringContainsString('MP3, WAV, OGG audio', $renderedAudio);

        // Custom accept override
        $renderedCustom = Blade::render('<x-mediaflow-input name="attachment" accept=".zip,.tar.gz" />');
        $this->assertStringContainsString('accept=".zip,.tar.gz"', $renderedCustom);
        $this->assertStringContainsString('data-accept=".zip,.tar.gz"', $renderedCustom);
    }
}

