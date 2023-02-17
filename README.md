# Google Cloud Storage filesystem driver for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-google-cloud-storage.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-google-cloud-storage)
[![run-tests](https://github.com/spatie/laravel-google-cloud-storage/actions/workflows/run-tests.yml/badge.svg)](https://github.com/spatie/laravel-google-cloud-storage/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-google-cloud-storage/Check%20&%20fix%20styling/main?label=code%20style)](https://github.com/spatie/laravel-google-cloud-storage/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-google-cloud-storage.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-google-cloud-storage)

---

Google Cloud Storage filesystem driver for Laravel 9 (using Flysystem v3 and its own GCS adapter).

**Looking for Laravel 8 support? Use the `v1` branch!**

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-google-cloud-storage.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-google-cloud-storage)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-google-cloud-storage
```

Next, add a new disk to your `filesystems.php` config:

```php
'gcs' => [
    'driver' => 'gcs',
    'key_file_path' => env('GOOGLE_CLOUD_KEY_FILE', null), // optional: /path/to/service-account.json
    'key_file' => [], // optional: Array of data that substitutes the .json file (see below)
    'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'your-project-id'), // optional: is included in key file
    'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'your-bucket'),
    'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', ''), // optional: /default/path/to/apply/in/bucket
    'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', null), // see: Public URLs below
    'apiEndpoint' => env('GOOGLE_CLOUD_STORAGE_API_ENDPOINT', null), // set storageClient apiEndpoint
    'visibility' => 'public', // optional: public|private
    'visibility_handler' => null, // optional: set to \League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility::class to enable uniform bucket level access
    'metadata' => ['cacheControl'=> 'public,max-age=86400'], // optional: default metadata
],
```

## Usage

```php
$disk = Storage::disk('gcs');

$disk->put('avatars/1', $fileContents);
$exists = $disk->exists('file.jpg');
$time = $disk->lastModified('file1.jpg');
$disk->copy('old/file1.jpg', 'new/file1.jpg');
$disk->move('old/file1.jpg', 'new/file1.jpg');
$url = $disk->url('folder/my_file.txt');
$url = $disk->temporaryUrl('folder/my_file.txt', now()->addMinutes(30));
$disk->setVisibility('folder/my_file.txt', 'public');
```

See https://laravel.com/docs/master/filesystem for full list of available functionality.

### Authentication

The Google Client uses a few methods to determine how it should authenticate with the Google API.

1. If you specify a path in the key `key_file` in  disk config, that json credentials file will be used.
2. If the `GOOGLE_APPLICATION_CREDENTIALS` env var is set, it will use that.
   ```php
   putenv('GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json');
   ```
3. It will then try load the key file from a 'well known path':
    * windows: %APPDATA%/gcloud/application_default_credentials.json
    * others: $HOME/.config/gcloud/application_default_credentials.json

4. If running in **Google App Engine**, the built-in service account associated with the application will be used.
5. If running in **Google Compute Engine**, the built-in service account associated with the virtual machine instance will be used.
6. If you want to authenticate directly without using a json file, you can specify an array for `key_file` in disk config with this data:
    ```php
    'key_file' => [
        'type' => env('GOOGLE_CLOUD_ACCOUNT_TYPE'),
        'private_key_id' => env('GOOGLE_CLOUD_PRIVATE_KEY_ID'),
        'private_key' => env('GOOGLE_CLOUD_PRIVATE_KEY'),
        'client_email' => env('GOOGLE_CLOUD_CLIENT_EMAIL'),
        'client_id' => env('GOOGLE_CLOUD_CLIENT_ID'),
        'auth_uri' => env('GOOGLE_CLOUD_AUTH_URI'),
        'token_uri' => env('GOOGLE_CLOUD_TOKEN_URI'),
        'auth_provider_x509_cert_url' => env('GOOGLE_CLOUD_AUTH_PROVIDER_CERT_URL'),
        'client_x509_cert_url' => env('GOOGLE_CLOUD_CLIENT_CERT_URL'),
    ],
    ```

### Public URLs

The adapter implements a `getUrl($path)` method which returns a public url to a file.
>**Note:** Method available for Laravel 5.2 and higher. If used on 5.1, it will throw an exception.

```php
$disk = Storage::disk('gcs');
$url = $disk->url('folder/my_file.txt');
// http://storage.googleapis.com/bucket-name/folder/my_file.txt
```

If you configure a `path_prefix` in your config:
```php
$disk = Storage::disk('gcs');
$url = $disk->url('folder/my_file.txt');
// http://storage.googleapis.com/bucket-name/path-prefix/folder/my_file.txt
```

If you configure a custom `apiEndpoint` in your config:
```php
$disk = Storage::disk('gcs');
$url = $disk->url('folder/my_file.txt');
// http://your-custom-domain.com/bucket-name/path-prefix/folder/my_file.txt
```

For a custom domain (storage api uri), you will need to configure a CNAME DNS entry pointing to `storage.googleapis.com`.

Please see https://cloud.google.com/storage/docs/xml-api/reference-uris#cname for further instructions.

### Temporary / Signed URLs

With the latest adapter versions, you can easily generate a signed URLs for files that are not publicly visible by default.

```php
$disk = Storage::disk('gcs');
$url = $disk->temporaryUrl('folder/my_file.txt', now()->addMinutes(30));
// https://storage.googleapis.com/test-bucket/folder/my_file.txt?GoogleAccessId=test-bucket%40test-gcp.iam.gserviceaccount.com&Expires=1571151576&Signature=tvxN1OS1txkWAUF0cCR3FWK%seRZXtFu42%04%YZACYL2zFQxA%uwdGEmdO1KgsHR3vBF%I9KaEzPbl4b7ic2IWUuo8Jh3IoZFqdTQec3KypjDtt%02DGwm%OO6pWDVV421Yp4z520%o5oMqGBtV8B3XmjW2PH76P3uID2QY%AlFxn23oE9PBoM2wXr8pDXhMPwZNJ0FtckSc26O8PmfVsG7Jvln%CQTU57IFyB7JnNxz5tQpc2hPTHbCGrcxVPEISvdOamW3I%83OsXr5raaYYBPcuumDnAmrK%cyS9%Ky2fL2B2shFO2cz%KRu79DBPqtnP2Zf1mJWBTwxVUCK2jxEEYcXBXtdOszIvlI6%tp2XfVwYxLNFU
```

## Uniform bucket-level access

Google Cloud Storage allows setting permissions at the bucket level i.e. "Uniform bucket-level access".

Initially, the error "Cannot insert legacy ACL for an object when uniform bucket-level access is enabled" is observed.

When uploading files to such buckets ensure the `visibility_handler` within the configuration file is set as follows:

```php
'visibility_handler' => \League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility::class,
```

Please see https://cloud.google.com/storage/docs/access-control/signed-urls and https://laravel.com/docs/6.x/filesystem for more info.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Superbalist](https://github.com/Superbalist) for the original GCS adapter package
- [Alex Vanderbist](https://github.com/alexvanderbist)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
