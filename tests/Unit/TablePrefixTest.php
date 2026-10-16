<?php

namespace MediaFlow\Laravel\Tests\Unit;

use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Models\MediaFlowSetting;
use MediaFlow\Laravel\Models\MediaFolder;
use MediaFlow\Laravel\Models\MediaVariant;
use MediaFlow\Laravel\Models\UploadSession;
use MediaFlow\Laravel\Tests\TestCase;

class TablePrefixTest extends TestCase
{
    public function test_default_table_names(): void
    {
        $this->assertEquals('mediaflow_media', (new Media)->getTable());
        $this->assertEquals('mediaflow_folders', (new MediaFolder)->getTable());
        $this->assertEquals('mediaflow_variants', (new MediaVariant)->getTable());
        $this->assertEquals('mediaflow_upload_sessions', (new UploadSession)->getTable());
        $this->assertEquals('mediaflow_settings', (new MediaFlowSetting)->getTable());
    }

    public function test_configurable_table_names_via_config(): void
    {
        config([
            'mediaflow.tables.media' => 'custom_media_items',
            'mediaflow.tables.folders' => 'custom_media_folders',
            'mediaflow.tables.variants' => 'custom_media_variants',
            'mediaflow.tables.upload_sessions' => 'custom_upload_sessions',
            'mediaflow.tables.settings' => 'custom_media_settings',
        ]);

        $this->assertEquals('custom_media_items', (new Media)->getTable());
        $this->assertEquals('custom_media_folders', (new MediaFolder)->getTable());
        $this->assertEquals('custom_media_variants', (new MediaVariant)->getTable());
        $this->assertEquals('custom_upload_sessions', (new UploadSession)->getTable());
        $this->assertEquals('custom_media_settings', (new MediaFlowSetting)->getTable());
    }
}
