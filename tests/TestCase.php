<?php

namespace Spatie\GoogleCloudStorage\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\GoogleCloudStorage\GoogleCloudStorageServiceProvider;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            GoogleCloudStorageServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('filesystem.default', 'gcs');
    }
}
