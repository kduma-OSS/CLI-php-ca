<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Adapter\SqliteAdapterConfiguration;
use KDuma\SimpleDAL\Adapter\Database\DatabaseAdapter;

it('constructs from array with resolved path', function () {
    $adapter = SqliteAdapterConfiguration::fromArray(['type' => 'sqlite', 'path' => './db.sqlite'], '/home/user');

    expect($adapter)->toBeInstanceOf(SqliteAdapterConfiguration::class)
        ->and($adapter->path)->toBe('/home/user/db.sqlite');
});

it('returns correct array structure', function () {
    $adapter = new SqliteAdapterConfiguration(path: '/data/ca.sqlite');

    expect($adapter->toArray())->toBe([
        'type' => 'sqlite',
        'path' => '/data/ca.sqlite',
    ]);
});

it('creates a DatabaseAdapter', function () {
    $path = sys_get_temp_dir() . '/php-ca-test-' . uniqid() . '.sqlite';

    try {
        $adapter = new SqliteAdapterConfiguration(path: $path);
        expect($adapter->createAdapter())->toBeInstanceOf(DatabaseAdapter::class);
    } finally {
        @unlink($path);
    }
});
