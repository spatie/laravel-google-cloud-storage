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
            $config = $this->prepareConfig($config);
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

        $pathPrefix = Arr::get($config, 'root');
        $visibility = Arr::get($config, 'visibility');
        $visibilityHandlerClass = Arr::get($config, 'visibility_handler');
        $visibilityHandler = $visibilityHandlerClass ? new $visibilityHandlerClass() : null;

        $defaultVisibility = in_array(
            $visibility,
            [
                Visibility::PRIVATE,
                Visibility::PUBLIC,
            ]
        ) ? $visibility : Visibility::PRIVATE;

        return new FlysystemGoogleCloudStorageAdapter($bucket, $pathPrefix, $visibilityHandler, $defaultVisibility);
    }

    protected function createClient(array $config): StorageClient
    {
        $options = [];

        if ($keyFilePath = Arr::get($config, 'keyFilePath')) {
            $options['keyFilePath'] = $keyFilePath;
        }

        if ($keyFile = Arr::get($config, 'keyFile')) {
            $options['keyFile'] = $keyFile;
        }

        if ($projectId = Arr::get($config, 'projectId')) {
            $options['projectId'] = $projectId;
        }

        if ($apiEndpoint = Arr::get($config, 'apiEndpoint')) {
            $options['apiEndpoint'] = $apiEndpoint;
        }

        return new StorageClient($options);
    }

    protected function prepareConfig(array $config): array
    {
        // Set root prefix to '' if none of the prefix params has been set
        if (! Arr::hasAny($config, ['root', 'pathPrefix', 'path_prefix'])) {
            $config['root'] = '';
        } // only reset root if it wasn't provided in the configuration
        elseif (! Arr::has($config, 'root') && Arr::hasAny($config, ['pathPrefix', 'path_prefix'])) {
            $config['root'] = Arr::get($config, 'pathPrefix') ?? Arr::get($config, 'path_prefix');
        }

        // Google's SDK expects camelCase keys, but we (often) use snake_case in the config.

        if ($keyFilePath = Arr::get($config, 'keyFilePath', Arr::get($config, 'key_file_path'))) {
            $config['keyFilePath'] = $keyFilePath;
        }

        if ($keyFile = Arr::get($config, 'keyFile', Arr::get($config, 'key_file'))) {
            $config['keyFile'] = $keyFile;
        }

        if ($projectId = Arr::get($config, 'projectId', Arr::get($config, 'project_id'))) {
            $config['projectId'] = $projectId;
        }

        if ($apiEndpoint = Arr::get($config, 'apiEndpoint')) {
            $config['apiEndpoint'] = $apiEndpoint;
        }

        if ($storageApiUri = Arr::get($config, 'storage_api_uri')) {
            $config['storageApiUri'] = $storageApiUri;
        }

        return $config;
    }
}
