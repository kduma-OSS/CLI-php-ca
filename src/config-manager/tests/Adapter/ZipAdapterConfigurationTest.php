<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Adapter\ZipAdapterConfiguration;
use KDuma\SimpleDAL\Adapter\Flysystem\FlysystemAdapter;

it('constructs from array with resolved path', function () {
    $adapter = ZipAdapterConfiguration::fromArray(['type' => 'zip', 'path' => './archive.zip'], '/home/user');

    expect($adapter)->toBeInstanceOf(ZipAdapterConfiguration::class)
        ->and($adapter->path)->toBe('/home/user/archive.zip');
});

it('returns correct array structure', function () {
    $adapter = new ZipAdapterConfiguration(path: '/data/ca.zip');

    expect($adapter->toArray())->toBe([
        'type' => 'zip',
        'path' => '/data/ca.zip',
    ]);
});

it('creates a FlysystemAdapter', function () {
    $path = sys_get_temp_dir().'/php-ca-test-'.uniqid().'.zip';

    $adapter = new ZipAdapterConfiguration(path: $path);
    expect($adapter->createAdapter())->toBeInstanceOf(FlysystemAdapter::class);

    if (file_exists($path)) {
        unlink($path);
    }
});
