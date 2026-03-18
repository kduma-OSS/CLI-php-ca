<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Adapter\MySqlAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\StringValueProvider;

it('parses all fields with defaults', function () {
    $adapter = MySqlAdapterConfiguration::fromArray([
        'type' => 'mysql',
        'database' => 'php_ca',
    ], '/base');

    expect($adapter->host)->toBe('127.0.0.1')
        ->and($adapter->port)->toBe(3306)
        ->and($adapter->database)->toBe('php_ca')
        ->and($adapter->username)->toBeInstanceOf(StringValueProvider::class)
        ->and($adapter->username->resolve())->toBe('root')
        ->and($adapter->password)->toBeInstanceOf(StringValueProvider::class)
        ->and($adapter->password->resolve())->toBe('');
});

it('parses explicit string values', function () {
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
        ->and($adapter->username->resolve())->toBe('ca_user')
        ->and($adapter->password->resolve())->toBe('s3cret');
});

it('parses ValueProvider objects for credentials', function () {
    putenv('MYSQL_USER=env_user');
    putenv('MYSQL_PASS=env_pass');

    $adapter = MySqlAdapterConfiguration::fromArray([
        'type' => 'mysql',
        'database' => 'ca_db',
        'username' => ['type' => 'env', 'variable' => 'MYSQL_USER'],
        'password' => ['type' => 'env', 'variable' => 'MYSQL_PASS'],
    ], '/base');

    expect($adapter->username->resolve())->toBe('env_user')
        ->and($adapter->password->resolve())->toBe('env_pass');

    putenv('MYSQL_USER');
    putenv('MYSQL_PASS');
});

it('throws on missing database', function () {
    MySqlAdapterConfiguration::fromArray(['type' => 'mysql'], '/base');
})->throws(InvalidArgumentException::class, 'MySQL adapter requires "database" option.');

it('returns correct array structure', function () {
    $adapter = new MySqlAdapterConfiguration(
        host: 'localhost',
        port: 3306,
        database: 'php_ca',
        username: new StringValueProvider('root'),
        password: new StringValueProvider('pass'),
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
        username: new StringValueProvider('root'),
        password: new StringValueProvider(''),
    );

    $adapter->createAdapter();
})->throws(RuntimeException::class, 'MySQL adapter is not yet implemented.');
