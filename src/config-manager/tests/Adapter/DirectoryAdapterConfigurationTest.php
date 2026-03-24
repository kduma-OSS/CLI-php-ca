<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Adapter\DirectoryAdapterConfiguration;
use KDuma\SimpleDAL\Adapter\Flysystem\FlysystemAdapter;

it('constructs from array with resolved path', function () {
    $adapter = DirectoryAdapterConfiguration::fromArray(['type' => 'directory', 'path' => './certs'], '/home/user');

    expect($adapter)->toBeInstanceOf(DirectoryAdapterConfiguration::class)
        ->and($adapter->path)->toBe('/home/user/certs');
});

it('returns correct array structure', function () {
    $adapter = new DirectoryAdapterConfiguration(path: '/data/certs');

    expect($adapter->toArray())->toBe([
        'type' => 'directory',
        'path' => '/data/certs',
    ]);
});

it('creates a FlysystemAdapter', function () {
    $dir = sys_get_temp_dir().'/php-ca-test-'.uniqid();
    mkdir($dir, 0755, true);

    try {
        $adapter = new DirectoryAdapterConfiguration(path: $dir);
        expect($adapter->createAdapter())->toBeInstanceOf(FlysystemAdapter::class);
    } finally {
        rmdir($dir);
    }
});
