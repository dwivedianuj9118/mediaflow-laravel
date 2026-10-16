<?php

namespace MediaFlow\Laravel\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use MediaFlow\Laravel\Events\MediaReady;
use MediaFlow\Laravel\Facades\MediaFlow;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Tests\TestCase;

class UploadTest extends TestCase
{
    public function test_can_upload_image_via_facade(): void
    {
        Event::fake([MediaReady::class]);

        $file = UploadedFile::fake()->image('banner.jpg', 1200, 800);

        $media = MediaFlow::upload($file, [
            'folder' => 'marketing',
            'collection' => 'banners',
            'visibility' => 'public',
        ]);

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('ready', $media->status);
        $this->assertEquals('marketing', $media->folder->name);
        $this->assertEquals('banners', $media->collection);
        $this->assertEquals('banner.jpg', $media->original_name);
        $this->assertNotNull($media->checksum);

        // Verify disk storage
        $adapter = MediaFlow::disk($media->disk);
        $this->assertTrue($adapter->exists($media->storage_key));

        // Verify variants were created
        $this->assertGreaterThan(0, $media->variants()->count());

        Event::assertDispatched(MediaReady::class);
    }

    public function test_can_upload_image_via_http_endpoint(): void
    {
        $file = UploadedFile::fake()->image('photo.png', 600, 600);

        $response = $this->postJson(route('mediaflow.upload'), [
            'file' => $file,
            'folder' => 'uploads',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('media.original_name', 'photo.png');
        $response->assertJsonPath('media.status', 'ready');

        $this->assertDatabaseHas('mediaflow_media', [
            'original_name' => 'photo.png',
            'status' => 'ready',
        ]);
    }

    public function test_upload_rejects_disallowed_mime_types(): void
    {
        config()->set('mediaflow.allowed_mimes', ['image/jpeg']);

        $file = UploadedFile::fake()->create('script.sh', 10, 'application/x-sh');

        $response = $this->postJson(route('mediaflow.upload'), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_can_upload_using_folder_id_and_saves_to_same_folder(): void
    {
        $folder = \MediaFlow\Laravel\Models\MediaFolder::create(['name' => 'Documents']);

        $file = UploadedFile::fake()->image('report.png', 400, 400);

        $response = $this->postJson(route('mediaflow.upload'), [
            'file' => $file,
            'folder_id' => $folder->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('media.folder_id', $folder->id);

        $this->assertDatabaseHas('mediaflow_media', [
            'original_name' => 'report.png',
            'folder_id' => $folder->id,
        ]);
    }

    public function test_can_upload_using_string_folder_id_from_form_data(): void
    {
        $folder = \MediaFlow\Laravel\Models\MediaFolder::create(['name' => 'Archive']);

        $file = UploadedFile::fake()->image('invoice.png', 400, 400);

        $response = $this->postJson(route('mediaflow.upload'), [
            'file' => $file,
            'folder_id' => (string) $folder->id,
            'folder' => (string) $folder->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('media.folder_id', $folder->id);

        $this->assertDatabaseHas('mediaflow_media', [
            'original_name' => 'invoice.png',
            'folder_id' => $folder->id,
        ]);
    }

    public function test_upload_using_folder_name_reuses_existing_folder(): void
    {
        $folder = \MediaFlow\Laravel\Models\MediaFolder::create(['name' => 'Projects']);

        $file = UploadedFile::fake()->image('mockup.png', 400, 400);

        $response = $this->postJson(route('mediaflow.upload'), [
            'file' => $file,
            'folder' => 'Projects',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('media.folder_id', $folder->id);

        // Ensure no duplicate folder was created
        $this->assertEquals(1, \MediaFlow\Laravel\Models\MediaFolder::where('name', 'Projects')->count());
    }

    public function test_csrf_token_endpoint_returns_token(): void
    {
        $response = $this->getJson(route('mediaflow.csrf-token'));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('csrf_token'));
    }
}
