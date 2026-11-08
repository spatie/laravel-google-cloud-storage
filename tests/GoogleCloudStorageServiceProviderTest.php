<?php

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use Spatie\GoogleCloudStorage\GoogleCloudStorageAdapter;

it('can create a gcs disk', function () {
    $disk = Storage::build([
        'driver' => 'gcs',
        'root' => fake()->uuid(),
        'bucket' => fake()->word(),
    ]);

    Storage::set('gcs', $disk);

    expect(Storage::disk('gcs'))->toBeInstanceOf(GoogleCloudStorageAdapter::class);
});

it('stores files of scoped disks under the scoped prefix', function (string $root, string $expectedPath) {
    $disk = Storage::build([
        'driver' => 'scoped',
        'disk' => [
            'driver' => 'gcs',
            'root' => $root,
            'bucket' => 'my-bucket',
        ],
        'prefix' => 'scoped',
    ]);

    expect($disk->getAdapter()->publicUrl('file.txt', new Config()))
        ->toBe("https://storage.googleapis.com/my-bucket/{$expectedPath}")
        ->and($disk->url('file.txt'))
        ->toBe("https://storage.googleapis.com/my-bucket/{$expectedPath}");
})->with([
    'with a root' => ['root', 'root/scoped/file.txt'],
    'without a root' => ['', 'scoped/file.txt'],
]);
