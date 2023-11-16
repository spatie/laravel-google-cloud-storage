<?php

namespace Spatie\GoogleCloudStorage;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        $visibilityHandlerClass = Arr::get($config, 'visibilityHandler');
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
        // Google's SDK expects camelCase keys, but we can use snake_case in the config.
        foreach ($config as $key => $value) {
            $config[Str::camel($key)] = $value;
        }

        if (! Arr::has($config, 'root')) {
            $config['root'] = Arr::get($config, 'pathPrefix') ?? '';
        }

        return $config;
    }
}
