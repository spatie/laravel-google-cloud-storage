<?php

use Illuminate\Support\Facades\Storage;
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
