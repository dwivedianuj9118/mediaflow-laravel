<?php

namespace MediaFlow\Laravel\Console\Commands;

use Illuminate\Console\Command;
use MediaFlow\Laravel\Models\Media;
use MediaFlow\Laravel\Storage\StorageAdapterRegistry;

class VerifyMediaStorageCommand extends Command
{
    protected $signature = 'mediaflow:verify-storage {--disk= : Specify a disk to verify}';
    protected $description = 'Verify that media records match real objects in configured storage';

    public function handle(StorageAdapterRegistry $registry): int
    {
        $diskOption = $this->option('disk');
        $query = Media::query();

        if ($diskOption) {
            $query->where('disk', $diskOption);
        }

        $mediaList = $query->get();
        $this->info("Checking {$mediaList->count()} media records against storage...");

        $missing = 0;
        $verified = 0;

        foreach ($mediaList as $media) {
            $adapter = $registry->get($media->disk);
            if ($adapter->exists($media->storage_key)) {
                $verified++;
            } else {
                $missing++;
                $this->warn("Missing file: ID {$media->id} [{$media->storage_key}] on disk [{$media->disk}]");
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records Checked', $mediaList->count()],
                ['Verified Present', $verified],
                ['Missing Objects', $missing],
            ]
        );

        return $missing > 0 ? self::FAILURE : self::SUCCESS;
    }
}
