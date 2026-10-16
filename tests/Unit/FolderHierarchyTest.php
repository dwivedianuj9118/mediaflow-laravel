<?php

namespace MediaFlow\Laravel\Tests\Unit;

use MediaFlow\Laravel\Models\MediaFolder;
use MediaFlow\Laravel\Tests\TestCase;

class FolderHierarchyTest extends TestCase
{
    public function test_folder_path_and_breadcrumbs_are_computed_correctly(): void
    {
        $root = MediaFolder::create(['name' => 'Blog']);
        $sub = MediaFolder::create(['name' => 'Covers', 'parent_id' => $root->id]);
        $subSub = MediaFolder::create(['name' => '2026', 'parent_id' => $sub->id]);

        $this->assertEquals('blog', $root->path);
        $this->assertEquals('blog/covers', $sub->path);
        $this->assertEquals('blog/covers/2026', $subSub->path);

        $crumbs = $subSub->breadcrumbs();
        $this->assertCount(3, $crumbs);
        $this->assertEquals('Blog', $crumbs[0]['name']);
        $this->assertEquals('Covers', $crumbs[1]['name']);
        $this->assertEquals('2026', $crumbs[2]['name']);
    }

    public function test_circular_folder_moves_are_prevented(): void
    {
        $parent = MediaFolder::create(['name' => 'Parent']);
        $child = MediaFolder::create(['name' => 'Child', 'parent_id' => $parent->id]);
        $grandChild = MediaFolder::create(['name' => 'GrandChild', 'parent_id' => $child->id]);

        // Child can move to root
        $this->assertTrue($child->canMoveTo(null));

        // Parent cannot move into itself
        $this->assertFalse($parent->canMoveTo($parent->id));

        // Parent cannot move into its own child or grandchild
        $this->assertFalse($parent->canMoveTo($child->id));
        $this->assertFalse($parent->canMoveTo($grandChild->id));

        // Grandchild CAN move into parent
        $this->assertTrue($grandChild->canMoveTo($parent->id));
    }

    public function test_folder_deletion_cascades_children_safely(): void
    {
        $parent = MediaFolder::create(['name' => 'Parent Folder']);
        $childFolder = MediaFolder::create(['name' => 'Sub Folder', 'parent_id' => $parent->id]);

        $response = $this->deleteJson('/mediaflow/folders/' . $parent->id);
        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('mediaflow_folders', ['id' => $parent->id]);
        $this->assertDatabaseHas('mediaflow_folders', ['id' => $childFolder->id, 'parent_id' => null]);
    }
}
