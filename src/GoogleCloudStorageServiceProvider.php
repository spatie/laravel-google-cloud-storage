<?php

namespace Spatie\GoogleCloudStorage;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter as FlysystemGoogleCloudStorageAdapter;
use League\Flysystem\Visibility;

class GoogleCloudStorageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Storage::extend('gcs', function ($_app, $config) {
            $client = $this->createClient($config);
            $adapter = $this->createAdapter($client, $config);

            return new GoogleCloudStorageAdapter(
                new Flysystem($adapter, $config),
                $adapter,
                $config,
                $client,
            );
        });
    }

    protected function createAdapter(StorageClient $client, array $config): FlysystemGoogleCloudStorageAdapter
    {
        $bucket = $client->bucket(Arr::get($config, 'bucket'));

        $pathPrefix = Arr::get($config, 'pathPrefix') ?? Arr::get($config, 'path_prefix') ?? '';
        $visibility = Arr::get($config, 'visibility');
        $defaultVisibility = in_array(
            $visibility,
            [
                Visibility::PRIVATE,
                Visibility::PUBLIC,
            ]
        ) ? $visibility : Visibility::PRIVATE;

        return new FlysystemGoogleCloudStorageAdapter($bucket, $pathPrefix, null, $defaultVisibility);
    }

    protected function createClient(array $config): StorageClient
    {
        $options = [];

        // Google's SDK expects camelCase keys, but we (often) use snake_case in the config.

        if ($keyFilePath = Arr::get($config, 'keyFilePath', Arr::get($config, 'key_file_path'))) {
            $options['keyFilePath'] = $keyFilePath;
        }

        if ($keyFile = Arr::get($config, 'keyFile', Arr::get($config, 'key_file'))) {
            $options['keyFile'] = $keyFile;
        }

        if ($projectId = Arr::get($config, 'projectId', Arr::get($config, 'project_id'))) {
            $options['projectId'] = $projectId;
        }

        return new StorageClient($options);
    }
}
