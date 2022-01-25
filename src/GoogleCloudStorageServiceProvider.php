<?php

namespace Spatie\GoogleCloudStorage;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;

class GoogleCloudStorageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Storage::extend('gcs', function ($_app, $config) {
            return $this->createFilesystemAdapter($config);
        });
    }

    protected function createFilesystemAdapter(array $config): FilesystemAdapter
    {
        $config = Arr::only($config, [
            'bucket',
            'path_prefix',
            'visibility',
            'disable_asserts',
            'storage_api_uri',
            'metadata',
        ]);
        $adapter = $this->createAdapter($config);

        return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
    }

    protected function createAdapter(array $config): GoogleCloudStorageAdapter
    {
        $storageClient = $this->createClient($config);
        $bucket = $storageClient->bucket(Arr::get($config, 'bucket'));
        $pathPrefix = Arr::get($config, 'path_prefix');
        $visibility = Arr::get($config, 'visibility');
        $defaultVisibility = in_array(
            $visibility,
            [
                Visibility::PRIVATE,
                Visibility::PUBLIC,
            ]
        ) ? $visibility : Visibility::PRIVATE;

        $storageApiUri = Arr::get($config, 'storage_api_uri');

        return new GoogleCloudStorageAdapter($bucket, $pathPrefix, null, $defaultVisibility, $storageApiUri);
    }

    protected function createClient(array $config): StorageClient
    {
        $options = [];

        if ($keyFilePath = Arr::get($config, 'key_file_path')) {
            $options['keyFilePath'] = $keyFilePath;
        }

        if ($keyFile = Arr::get($config, 'key_file')) {
            $options['keyFile'] = $keyFile;
        }

        if ($projectId = Arr::get($config, 'project_id')) {
            $options['projectId'] = $projectId;
        }

        return new StorageClient($options);
    }
}
