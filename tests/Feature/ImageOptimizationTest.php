<?php

namespace MediaFlow\Laravel\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use MediaFlow\Laravel\Events\MediaReady;
use MediaFlow\Laravel\Facades\MediaFlow;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Tests\TestCase;

class ImageOptimizationTest extends TestCase
{
    public function test_can_optimize_and_convert_media_in_place(): void
    {
        Event::fake([MediaReady::class]);

        // Create a 1000x800 test JPEG image with format => keep so we can test manual conversion
        $file = UploadedFile::fake()->image('nature.jpg', 1000, 800);
        $media = MediaFlow::upload($file, [
            'folder' => 'landscapes',
            'visibility' => 'public',
            'format' => 'keep',
        ]);

        $this->assertEquals('image/jpeg', $media->mime_type);
        $originalId = $media->id;

        // Perform in-place optimization and WebP conversion
        $response = $this->postJson(route('mediaflow.media.optimize', $media->id), [
            'format' => 'webp',
            'quality' => 70,
            'max_width' => 800,
            'action' => 'replace',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('media.id', $originalId);
        $response->assertJsonPath('media.mime_type', 'image/webp');

        $media->refresh();
        $this->assertEquals('image/webp', $media->mime_type);
        $this->assertStringEndsWith('.webp', $media->original_name);
        $this->assertLessThanOrEqual(800, $media->width);

        // Verify storage file exists on disk
        $adapter = MediaFlow::disk($media->disk);
        $this->assertTrue($adapter->exists($media->storage_key));
    }

    public function test_can_optimize_and_save_as_new_copy(): void
    {
        Event::fake([MediaReady::class]);

        $file = UploadedFile::fake()->image('banner.png', 800, 600);
        $media = MediaFlow::upload($file, [
            'folder' => 'graphics',
            'visibility' => 'public',
            'format' => 'keep',
        ]);

        $response = $this->postJson(route('mediaflow.media.optimize', $media->id), [
            'format' => 'webp',
            'quality' => 80,
            'action' => 'copy',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $newMediaId = $response->json('media.id');

        $this->assertNotEquals($media->id, $newMediaId);

        // Verify original still exists as PNG
        $media->refresh();
        $this->assertEquals('image/png', $media->mime_type);
        $this->assertEquals('banner.png', $media->original_name);

        // Verify new copy is WebP
        $newMedia = Media::findOrFail($newMediaId);
        $this->assertEquals('image/webp', $newMedia->mime_type);
        $this->assertStringContainsString('optimized', $newMedia->original_name);
    }

    public function test_optimize_rejects_non_image_media(): void
    {
        $nonImageMedia = Media::create([
            'uuid' => '99999999-9999-9999-9999-999999999999',
            'original_name' => 'document.pdf',
            'disk' => 'public',
            'storage_key' => 'media/document.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'status' => 'ready',
        ]);

        $response = $this->postJson(route('mediaflow.media.optimize', $nonImageMedia->id), [
            'format' => 'webp',
            'quality' => 80,
            'action' => 'replace',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Only images can be optimized or converted.');
    }

    public function test_optimize_validates_input_parameters(): void
    {
        $media = Media::create([
            'uuid' => '88888888-8888-8888-8888-888888888888',
            'original_name' => 'test.jpg',
            'disk' => 'public',
            'storage_key' => 'media/test.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'status' => 'ready',
        ]);

        // Quality too high (> 100)
        $response = $this->postJson(route('mediaflow.media.optimize', $media->id), [
            'format' => 'webp',
            'quality' => 150,
        ]);
        $response->assertStatus(422);

        // Invalid format
        $response = $this->postJson(route('mediaflow.media.optimize', $media->id), [
            'format' => 'gif_unsupported',
            'quality' => 80,
        ]);
        $response->assertStatus(422);
    }

    public function test_upload_auto_optimizes_and_converts_to_webp_by_default(): void
    {
        Event::fake([MediaReady::class]);

        $file = UploadedFile::fake()->image('camera_photo.jpg', 1200, 900);
        $media = MediaFlow::upload($file, [
            'folder' => 'uploads',
        ]);

        $this->assertEquals('image/webp', $media->mime_type);
        $this->assertEquals('webp', $media->extension);
        $this->assertStringEndsWith('.webp', $media->storage_key);
        $this->assertTrue($media->metadata['optimized'] ?? false);
        $this->assertEquals('webp', $media->metadata['format'] ?? null);

        // Verify file stored on disk is valid and exists
        $adapter = MediaFlow::disk($media->disk);
        $this->assertTrue($adapter->exists($media->storage_key));
    }
}

