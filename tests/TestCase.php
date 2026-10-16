<?php

namespace MediaFlow\Laravel\Tests;

use MediaFlow\Laravel\MediaFlowServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            MediaFlowServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup default in-memory database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup test disks
        $app['config']->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => sys_get_temp_dir() . '/mediaflow_test_public',
            'url' => 'http://localhost/storage',
            'visibility' => 'public',
        ]);

        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => sys_get_temp_dir() . '/mediaflow_test_local',
        ]);

        // Package default requires authentication; most feature tests exercise the
        // endpoints as a guest, so open access here. AccessControlTest and
        // SecurityHardeningTest turn it back on explicitly.
        $app['config']->set('mediaflow.auth.enabled', false);

        $app['config']->set('mediaflow.default_disk', 'public');
        $app['config']->set('mediaflow.temporary_disk', 'local');
    }
}
