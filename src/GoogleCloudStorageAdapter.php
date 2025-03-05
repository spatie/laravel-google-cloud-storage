<?php

namespace Spatie\GoogleCloudStorage;

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter as FlysystemGoogleCloudAdapter;

class GoogleCloudStorageAdapter extends FilesystemAdapter
{
    public function __construct(
        FilesystemOperator $driver,
        FlysystemGoogleCloudAdapter $adapter,
        array $config,
        protected StorageClient $client
    ) {
        parent::__construct($driver, $adapter, $config);
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @return string
     *
     * @throws \RuntimeException
     */
    public function url($path)
    {
        // Get the storage API URI from the configuration trim the trailing slash
        $storageApiUri = rtrim(Arr::get($this->config, 'storageApiUri'), '/');

        // Get the bucket name from the configuration
        $bucketName = Arr::get($this->config, 'bucket');

        // Construct the URL using the bucket name
        if ($bucketName) {
            $storageApiUri = "{$storageApiUri}/{$bucketName}";
        }

        return $this->concatPathToUrl($storageApiUri, $this->prefixer->prefixPath($path));
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     */
    public function temporaryUrl($path, $expiration, array $options = [])
    {
        if (Arr::get($this->config, 'storageApiUri')) {
            $options['bucketBoundHostname'] = Arr::get($this->config, 'storageApiUri');
        }

        return $this->getBucket()->object($this->prefixer->prefixPath($path))->signedUrl($expiration, $options);
    }

    /**
     * Get a temporary upload URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     */
    public function temporaryUploadUrl($path, $expiration, array $options = [])
    {
        if (Arr::get($this->config, 'storageApiUri')) {
            $options['bucketBoundHostname'] = Arr::get($this->config, 'storageApiUri');
        }

        return $this->getBucket()->object($this->prefixer->prefixPath($path))->beginSignedUploadSession($options);
    }

    public function getClient(): StorageClient
    {
        return $this->client;
    }

    private function getBucket(): Bucket
    {
        return $this->client->bucket(Arr::get($this->config, 'bucket'));
    }

    /**
     * Determine if temporary URLs can be generated.
     *
     * @return bool
     */
    public function providesTemporaryUrls(): bool
    {
        return true;
    }
}
