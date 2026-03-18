<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Adapter\AdapterConfigurationFactory;
use KDuma\PhpCA\ConfigManager\Adapter\DirectoryAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\MySqlAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\SqliteAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\ZipAdapterConfiguration;

it('returns all registered adapter types', function () {
    $factory = new AdapterConfigurationFactory();
    $types = $factory->getAdapterTypes();

    expect($types)->toHaveKeys(['directory', 'sqlite', 'zip', 'mysql'])
        ->and($types['directory'])->toBe(DirectoryAdapterConfiguration::class)
        ->and($types['sqlite'])->toBe(SqliteAdapterConfiguration::class)
        ->and($types['zip'])->toBe(ZipAdapterConfiguration::class)
        ->and($types['mysql'])->toBe(MySqlAdapterConfiguration::class);
});

it('creates correct class for each type', function (string $type, string $expectedClass) {
    $factory = new AdapterConfigurationFactory();
    $adapter = $factory->fromArray(['type' => $type, 'path' => './data'], '/base');

    expect($adapter)->toBeInstanceOf($expectedClass);
})->with([
    ['directory', DirectoryAdapterConfiguration::class],
    ['sqlite', SqliteAdapterConfiguration::class],
    ['zip', ZipAdapterConfiguration::class],
]);

it('throws on unknown type', function () {
    $factory = new AdapterConfigurationFactory();
    $factory->fromArray(['type' => 'mongodb'], '/base');
})->throws(InvalidArgumentException::class, 'Unknown adapter type "mongodb"');

it('returns correct type for each class', function (string $class, string $expectedType) {
    $type = AdapterConfigurationFactory::getTypeForClass($class);

    expect($type)->toBe($expectedType);
})->with([
    [DirectoryAdapterConfiguration::class, 'directory'],
    [SqliteAdapterConfiguration::class, 'sqlite'],
    [ZipAdapterConfiguration::class, 'zip'],
    [MySqlAdapterConfiguration::class, 'mysql'],
]);
