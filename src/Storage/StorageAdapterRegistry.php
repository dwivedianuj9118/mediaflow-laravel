<?php

namespace MediaFlow\Laravel\Storage;

use MediaFlow\Laravel\Contracts\StorageAdapter;

class StorageAdapterRegistry
{
    /**
     * @var array<string, StorageAdapter>
     */
    protected array $adapters = [];

    /**
     * Register a custom storage adapter for a disk.
     */
    public function registerAdapter(string $disk, StorageAdapter $adapter): void
    {
        $this->adapters[$disk] = $adapter;
    }

    /**
     * Resolve the storage adapter for the given disk.
     */
    public function get(?string $disk = null): StorageAdapter
    {
        $disk = $disk ?: config('mediaflow.default_disk', 'public');

        if (isset($this->adapters[$disk])) {
            return $this->adapters[$disk];
        }

        $driver = config("filesystems.disks.{$disk}.driver", 'local');

        if ($driver === 's3') {
            $adapter = new S3StorageAdapter($disk);
        } else {
            $adapter = new LocalStorageAdapter($disk);
        }

        $this->adapters[$disk] = $adapter;

        return $adapter;
    }
}
