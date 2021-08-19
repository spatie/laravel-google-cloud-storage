<?php

namespace Spatie\GoogleCloudStorage;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\Cache;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\CacheInterface;
use League\Flysystem\Cached\Storage\Memory as MemoryStore;
use League\Flysystem\Filesystem;
use Spatie\GoogleCloudStorageAdapter\GoogleCloudStorageAdapter;

class GoogleCloudStorageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $factory = $this->app->make('filesystem');

        /* @var FilesystemManager $factory */
        $factory->extend('gcs', function ($_app, $config) {
            $adapter = $this->createAdapter($config);

            return $this->createFilesystem($adapter, $config);
        });
    }

    protected function createAdapter(array $config): GoogleCloudStorageAdapter
    {
        $storageClient = $this->createClient($config);

        $bucket = $storageClient->bucket($config['bucket']);

        $pathPrefix = Arr::get($config, 'path_prefix');
        $storageApiUri = Arr::get($config, 'storage_api_uri');

        return new GoogleCloudStorageAdapter($storageClient, $bucket, $pathPrefix, $storageApiUri);
    }

    protected function createFilesystem(AdapterInterface $adapter, array $config): Filesystem
    {
        $cache = Arr::pull($config, 'cache');

        $config = Arr::only($config, ['visibility', 'disable_asserts', 'url', 'metadata']);

        if ($cache) {
            $adapter = new CachedAdapter($adapter, $this->createCacheStore($cache));
        }

        return new Filesystem($adapter, count($config) > 0 ? $config : null);
    }

    protected function createCacheStore(array | bool $config): CacheInterface
    {
        if ($config === true) {
            return new MemoryStore();
        }

        return new Cache(
            $this->app->get('cache')->store($config['store']),
            Arr::get($config, 'prefix', 'flysystem'),
            Arr::get($config, 'expire')
        );
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
