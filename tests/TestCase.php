<?php

namespace Spatie\GoogleCloudStorage\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\GoogleCloudStorage\GoogleCloudStorageServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GoogleCloudStorageServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('filesystem.default', 'gcs');
    }
}
