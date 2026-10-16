<?php

namespace MediaFlow\Laravel\Tests\Feature;

use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaFolder;
use MediaFlow\Laravel\Tests\TestCase;

class MediaManagerApiTest extends TestCase
{
    public function test_can_list_media_and_folders(): void
    {
        $folder = MediaFolder::create(['name' => 'Portfolio']);
        $media = Media::create([
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'folder_id' => $folder->id,
            'original_name' => 'design.jpg',
            'disk' => 'public',
            'storage_key' => 'media/2026/10/design.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 2048,
            'status' => 'ready',
        ]);

        $response = $this->getJson(route('mediaflow.manager', ['folder_id' => $folder->id]));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'media');
        $response->assertJsonPath('media.0.name', 'design.jpg');
    }

    public function test_can_rename_and_move_media(): void
    {
        $folderA = MediaFolder::create(['name' => 'Folder A']);
        $folderB = MediaFolder::create(['name' => 'Folder B']);

        $media = Media::create([
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'folder_id' => $folderA->id,
            'original_name' => 'file_a.png',
            'disk' => 'public',
            'storage_key' => 'media/2026/10/file_a.png',
            'mime_type' => 'image/png',
            'size_bytes' => 1024,
            'status' => 'ready',
        ]);

        $response = $this->patchJson(route('mediaflow.media.update', $media->id), [
            'name' => 'renamed_file.png',
            'folder_id' => $folderB->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('mediaflow_media', [
            'id' => $media->id,
            'original_name' => 'renamed_file.png',
            'folder_id' => $folderB->id,
        ]);
    }

    public function test_can_delete_single_media(): void
    {
        $media = Media::create([
            'uuid' => '44444444-4444-4444-4444-444444444441',
            'original_name' => 'single.jpg',
            'disk' => 'public',
            'storage_key' => 'media/single.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 500,
            'status' => 'ready',
        ]);

        $response = $this->deleteJson(route('mediaflow.media.destroy', $media->id));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertSoftDeleted('mediaflow_media', ['id' => $media->id]);
    }

    public function test_can_bulk_delete_media(): void
    {
        $media1 = Media::create([
            'uuid' => '33333333-3333-3333-3333-333333333331',
            'original_name' => 'item1.jpg',
            'disk' => 'public',
            'storage_key' => 'media/item1.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 500,
            'status' => 'ready',
        ]);

        $media2 = Media::create([
            'uuid' => '33333333-3333-3333-3333-333333333332',
            'original_name' => 'item2.jpg',
            'disk' => 'public',
            'storage_key' => 'media/item2.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 500,
            'status' => 'ready',
        ]);

        $response = $this->postJson(route('mediaflow.media.bulk'), [
            'action' => 'delete',
            'ids' => [(string) $media1->id, (int) $media2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('deleted_count', 2);
        $this->assertSoftDeleted('mediaflow_media', ['id' => $media1->id]);
        $this->assertSoftDeleted('mediaflow_media', ['id' => $media2->id]);
    }

    public function test_manager_can_query_by_folder_name_and_cleans_null_strings(): void
    {
        $folder = MediaFolder::create(['name' => 'Marketing']);
        $inside = Media::create([
            'uuid' => '55555555-5555-5555-5555-555555555551',
            'folder_id' => $folder->id,
            'original_name' => 'banner.jpg',
            'disk' => 'public',
            'storage_key' => 'media/banner.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 500,
            'status' => 'ready',
        ]);
        $root = Media::create([
            'uuid' => '55555555-5555-5555-5555-555555555552',
            'folder_id' => null,
            'original_name' => 'root_file.jpg',
            'disk' => 'public',
            'storage_key' => 'media/root_file.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 500,
            'status' => 'ready',
        ]);

        // Query by folder name string
        $respName = $this->getJson(route('mediaflow.manager', ['folder' => 'Marketing']));
        $respName->assertStatus(200);
        $respName->assertJsonCount(1, 'media');
        $respName->assertJsonPath('media.0.name', 'banner.jpg');

        // Query by folder_id = 'null' string should return root items
        $respRoot = $this->getJson(route('mediaflow.manager', ['folder_id' => 'null']));
        $respRoot->assertStatus(200);
        $respRoot->assertJsonPath('current_folder_id', null);
        $mediaNames = collect($respRoot->json('media'))->pluck('name');
        $this->assertTrue($mediaNames->contains('root_file.jpg'));
        $this->assertFalse($mediaNames->contains('banner.jpg'));
    }

    public function test_delete_media_with_relation_constraint_returns_warning(): void
    {
        $media = Media::create([
            'uuid' => '99999999-9999-9999-9999-999999999999',
            'original_name' => 'constrained.jpg',
            'disk' => 'public',
            'storage_key' => 'media/constrained.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 500,
            'status' => 'ready',
        ]);

        // Mock MediaFlow::delete to throw a foreign key QueryException
        \MediaFlow\Laravel\Facades\MediaFlow::partialMock()
            ->shouldReceive('delete')
            ->once()
            ->andThrow(new \Illuminate\Database\QueryException(
                'sqlite',
                'DELETE FROM mediaflow_media WHERE id = ?',
                [],
                new \Exception('Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails')
            ));

        $response = $this->deleteJson(route('mediaflow.media.destroy', $media->id));

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'is_relation_warning' => true,
        ]);
        $this->assertStringContainsString('linked to other records', $response->json('error'));
    }

    public function test_frontend_scope_isolates_media_and_hides_admin_folders(): void
    {
        $adminFolder = MediaFolder::create(['name' => 'admin-vault', 'slug' => 'admin-vault']);
        $adminMedia = Media::create([
            'uuid' => '44444444-4444-4444-4444-444444444444',
            'folder_id' => $adminFolder->id,
            'collection' => 'admin',
            'visibility' => 'private',
            'original_name' => 'confidential.pdf',
            'disk' => 'local',
            'storage_key' => 'media/2026/10/confidential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'status' => 'ready',
        ]);

        $frontendMedia = Media::create([
            'uuid' => '55555555-5555-5555-5555-555555555555',
            'collection' => 'blog',
            'visibility' => 'public',
            'original_name' => 'public_hero.webp',
            'disk' => 'public',
            'storage_key' => 'media/2026/10/public_hero.webp',
            'mime_type' => 'image/webp',
            'size_bytes' => 2048,
            'status' => 'ready',
        ]);

        // Request with frontend isolation and collection filter
        $response = $this->getJson(route('mediaflow.manager', [
            'mode' => 'picker',
            'scope' => 'frontend',
            'collection' => 'blog',
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'media');
        $response->assertJsonPath('media.0.name', 'public_hero.webp');
        // Admin root folder must NOT be returned in isolated collection mode
        $response->assertJsonCount(0, 'folders');
    }

    public function test_manager_filters_by_type_and_accept_for_videos_and_docs(): void
    {
        Media::create([
            'uuid' => '66666666-6666-6666-6666-666666666661',
            'original_name' => 'clip.mp4',
            'extension' => 'mp4',
            'disk' => 'public',
            'storage_key' => 'media/2026/10/clip.mp4',
            'mime_type' => 'video/mp4',
            'size_bytes' => 1048576,
            'status' => 'ready',
        ]);

        Media::create([
            'uuid' => '66666666-6666-6666-6666-666666666662',
            'original_name' => 'invoice.pdf',
            'extension' => 'pdf',
            'disk' => 'public',
            'storage_key' => 'media/2026/10/invoice.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 20480,
            'status' => 'ready',
        ]);

        Media::create([
            'uuid' => '66666666-6666-6666-6666-666666666663',
            'original_name' => 'song.mp3',
            'extension' => 'mp3',
            'disk' => 'public',
            'storage_key' => 'media/2026/10/song.mp3',
            'mime_type' => 'audio/mpeg',
            'size_bytes' => 524288,
            'status' => 'ready',
        ]);

        // Filter type=video
        $videoRes = $this->getJson(route('mediaflow.manager', ['mode' => 'picker', 'type' => 'video']));
        $videoRes->assertStatus(200);
        $videoRes->assertJsonCount(1, 'media');
        $videoRes->assertJsonPath('media.0.name', 'clip.mp4');
        $videoRes->assertJsonPath('media.0.is_video', true);

        // Filter type=docs
        $docRes = $this->getJson(route('mediaflow.manager', ['mode' => 'picker', 'type' => 'docs']));
        $docRes->assertStatus(200);
        $docRes->assertJsonCount(1, 'media');
        $docRes->assertJsonPath('media.0.name', 'invoice.pdf');
        $docRes->assertJsonPath('media.0.is_document', true);

        // Filter accept=".mp3"
        $audioRes = $this->getJson(route('mediaflow.manager', ['mode' => 'picker', 'accept' => '.mp3']));
        $audioRes->assertStatus(200);
        $audioRes->assertJsonCount(1, 'media');
        $audioRes->assertJsonPath('media.0.name', 'song.mp3');
        $audioRes->assertJsonPath('media.0.is_audio', true);
    }
}
