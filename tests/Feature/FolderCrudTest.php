<?php

namespace MediaFlow\Laravel\Tests\Feature;

use MediaFlow\Laravel\Models\MediaFolder;
use MediaFlow\Laravel\Tests\TestCase;

class FolderCrudTest extends TestCase
{
    public function test_can_create_folder_via_http(): void
    {
        $response = $this->postJson(route('mediaflow.folders.store'), [
            'name' => 'Documents',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('folder.name', 'Documents');

        $this->assertDatabaseHas('mediaflow_folders', [
            'name' => 'Documents',
        ]);
    }

    public function test_cannot_create_duplicate_folder_in_same_parent(): void
    {
        MediaFolder::create(['name' => 'Archive']);

        $response = $this->postJson(route('mediaflow.folders.store'), [
            'name' => 'Archive',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_can_rename_and_delete_folder(): void
    {
        $folder = MediaFolder::create(['name' => 'Old Name']);

        // Rename
        $updateResp = $this->patchJson(route('mediaflow.folders.update', $folder->id), [
            'name' => 'New Name',
        ]);
        $updateResp->assertStatus(200);
        $this->assertDatabaseHas('mediaflow_folders', ['name' => 'New Name']);

        // Delete
        $deleteResp = $this->deleteJson(route('mediaflow.folders.destroy', $folder->id));
        $deleteResp->assertStatus(200);
        $this->assertDatabaseMissing('mediaflow_folders', ['id' => $folder->id]);
    }
}
