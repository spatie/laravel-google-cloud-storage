<?php

namespace Spatie\GoogleCloudStorage;

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\Connection\Rest;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter as GCSAdapter;

class GoogleCloudStorageAdapter extends FilesystemAdapter
{
    protected StorageClient $client;

    public function __construct(
        FilesystemOperator $driver,
        GCSAdapter $adapter,
        array $config,
        StorageClient $client
    ) {
        parent::__construct($driver, $adapter, $config);

        $this->client = $client;
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
        $apiEndpoint = rtrim(Rest::DEFAULT_API_ENDPOINT, '/').'/'.ltrim(Arr::get($this->config, 'bucket'), '/');

        if (isset($this->config['apiEndpoint'])) {
            $apiEndpoint = $this->config['apiEndpoint'];
        }

        return $this->concatPathToUrl($apiEndpoint, $this->prefixer->prefixPath($path));
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
        return $this->getBucket()->object($path)->signedUrl($expiration, $options);

//        $command = $this->client->getCommand('GetObject', array_merge([
//            'Bucket' => $this->config['bucket'],
//            'Key' => $this->prefixer->prefixPath($path),
//        ], $options));
//
//        $uri = $this->client->createPresignedRequest(
//            $command, $expiration
//        )->getUri();
//
//        // If an explicit base URL has been set on the disk configuration then we will use
//        // it as the base URL instead of the default path. This allows the developer to
//        // have full control over the base path for this filesystem's generated URLs.
//        if (isset($this->config['temporary_url'])) {
//            $uri = $this->replaceBaseUrl($uri, $this->config['temporary_url']);
//        }
//
//        return (string) $uri;
    }

    private function getBucket(): Bucket
    {
        return $this->client->bucket(Arr::get($this->config, 'bucket'));
    }
}
