<?php

namespace Spatie\GoogleCloudStorage;

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\Connection\Rest;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter as BaseAdapter;
use League\Flysystem\GoogleCloudStorage\VisibilityHandler;
use League\Flysystem\Visibility;

class GoogleCloudStorageAdapter extends BaseAdapter
{
    protected string $storageApiUrl;

    public function __construct(
        Bucket $bucket,
        string $prefix = '',
        VisibilityHandler $visibilityHandler = null,
        string $defaultVisibility = Visibility::PRIVATE,
        ?string $storageApiUrl = null,
    ) {
        parent::__construct($bucket, $prefix, $visibilityHandler, $defaultVisibility);

        $this->storageApiUrl = $storageApiUrl ?? Rest::DEFAULT_API_ENDPOINT;
    }
}
