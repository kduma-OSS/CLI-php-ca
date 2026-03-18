<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Adapter\MySqlAdapterConfiguration;

it('parses all fields with defaults', function () {
    $adapter = MySqlAdapterConfiguration::fromArray([
        'type' => 'mysql',
        'database' => 'php_ca',
    ], '/base');

    expect($adapter->host)->toBe('127.0.0.1')
        ->and($adapter->port)->toBe(3306)
        ->and($adapter->database)->toBe('php_ca')
        ->and($adapter->username)->toBe('root')
        ->and($adapter->password)->toBe('');
});

it('parses explicit values', function () {
    $adapter = MySqlAdapterConfiguration::fromArray([
        'type' => 'mysql',
        'host' => 'db.example.com',
        'port' => 3307,
        'database' => 'ca_prod',
        'username' => 'ca_user',
        'password' => 's3cret',
    ], '/base');

    expect($adapter->host)->toBe('db.example.com')
        ->and($adapter->port)->toBe(3307)
        ->and($adapter->database)->toBe('ca_prod')
        ->and($adapter->username)->toBe('ca_user')
        ->and($adapter->password)->toBe('s3cret');
});

it('throws on missing database', function () {
    MySqlAdapterConfiguration::fromArray(['type' => 'mysql'], '/base');
})->throws(InvalidArgumentException::class, 'MySQL adapter requires "database" option.');

it('returns correct array structure', function () {
    $adapter = new MySqlAdapterConfiguration(
        host: 'localhost',
        port: 3306,
        database: 'php_ca',
        username: 'root',
        password: 'pass',
    );

    expect($adapter->toArray())->toBe([
        'type' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'php_ca',
        'username' => 'root',
        'password' => 'pass',
    ]);
});

it('throws RuntimeException on createAdapter', function () {
    $adapter = new MySqlAdapterConfiguration(
        host: 'localhost',
        port: 3306,
        database: 'php_ca',
        username: 'root',
        password: '',
    );

    $adapter->createAdapter();
})->throws(RuntimeException::class, 'MySQL adapter is not yet implemented.');
