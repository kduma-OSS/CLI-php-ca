<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Adapter\DirectoryAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\SqliteAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\ZipAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\CaConfiguration;
use KDuma\PhpCA\ConfigManager\CaConfigurationLoader;

it('loads a directory adapter config', function () {
    $loader = new CaConfigurationLoader;
    $config = $loader->load(['adapter' => ['type' => 'directory', 'path' => './data']], '/base');

    expect($config)->toBeInstanceOf(CaConfiguration::class)
        ->and($config->adapter)->toBeInstanceOf(DirectoryAdapterConfiguration::class)
        ->and($config->adapter->path)->toBe('/base/data');
});

it('loads a sqlite adapter config', function () {
    $loader = new CaConfigurationLoader;
    $config = $loader->load(['adapter' => ['type' => 'sqlite', 'path' => './db.sqlite']], '/base');

    expect($config->adapter)->toBeInstanceOf(SqliteAdapterConfiguration::class)
        ->and($config->adapter->path)->toBe('/base/db.sqlite');
});

it('loads a zip adapter config', function () {
    $loader = new CaConfigurationLoader;
    $config = $loader->load(['adapter' => ['type' => 'zip', 'path' => './archive.zip']], '/base');

    expect($config->adapter)->toBeInstanceOf(ZipAdapterConfiguration::class)
        ->and($config->adapter->path)->toBe('/base/archive.zip');
});

it('resolves relative path against basePath', function () {
    $loader = new CaConfigurationLoader;
    $config = $loader->load(['adapter' => ['type' => 'directory', 'path' => './sub/dir']], '/home/user');

    expect($config->adapter->path)->toBe('/home/user/sub/dir');
});

it('keeps absolute path as-is', function () {
    $loader = new CaConfigurationLoader;
    $config = $loader->load(['adapter' => ['type' => 'directory', 'path' => '/absolute/path']], '/base');

    expect($config->adapter->path)->toBe('/absolute/path');
});

it('throws on missing adapter key', function () {
    $loader = new CaConfigurationLoader;
    $loader->load([], '/base');
})->throws(InvalidArgumentException::class, 'Configuration must contain an "adapter" section.');

it('throws on missing adapter type', function () {
    $loader = new CaConfigurationLoader;
    $loader->load(['adapter' => ['path' => './data']], '/base');
})->throws(InvalidArgumentException::class, 'Adapter configuration must contain a "type" field.');

it('throws on unknown adapter type', function () {
    $loader = new CaConfigurationLoader;
    $loader->load(['adapter' => ['type' => 'redis', 'path' => './data']], '/base');
})->throws(InvalidArgumentException::class, 'Unknown adapter type "redis"');
