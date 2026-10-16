<?php

namespace MediaFlow\Laravel\Console\Commands;

use Illuminate\Console\Command;
use MediaFlow\Laravel\Upload\TemporaryUploadManager;

class CleanTemporaryUploadsCommand extends Command
{
    protected $signature = 'mediaflow:clean-temp {--dry-run : Only report expired sessions without deleting}';
    protected $description = 'Clean expired temporary upload sessions and orphaned files';

    public function handle(TemporaryUploadManager $manager): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Scanning for expired temporary uploads (dry-run)...' : 'Cleaning expired temporary uploads...');

        $count = $manager->cleanExpired($dryRun);

        if ($dryRun) {
            $this->info("Found {$count} expired temporary uploads that would be cleaned.");
        } else {
            $this->info("Successfully cleaned {$count} expired temporary uploads.");
        }

        return self::SUCCESS;
    }
}
