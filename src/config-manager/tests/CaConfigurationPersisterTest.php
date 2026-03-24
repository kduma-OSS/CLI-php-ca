<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Adapter\DirectoryAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\SqliteAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\CaConfiguration;
use KDuma\PhpCA\ConfigManager\CaConfigurationLoader;
use KDuma\PhpCA\ConfigManager\CaConfigurationPersister;

it('persists directory config to array', function () {
    $config = new CaConfiguration(
        adapter: new DirectoryAdapterConfiguration(path: '/data/certs'),
    );

    $persister = new CaConfigurationPersister;
    $array = $persister->toArray($config);

    expect($array)->toBe([
        'adapter' => [
            'type' => 'directory',
            'path' => '/data/certs',
        ],
    ]);
});

it('persists sqlite config to array', function () {
    $config = new CaConfiguration(
        adapter: new SqliteAdapterConfiguration(path: '/data/ca.sqlite'),
    );

    $persister = new CaConfigurationPersister;
    $array = $persister->toArray($config);

    expect($array)->toBe([
        'adapter' => [
            'type' => 'sqlite',
            'path' => '/data/ca.sqlite',
        ],
    ]);
});

it('round-trips through loader and persister', function () {
    $input = [
        'adapter' => [
            'type' => 'directory',
            'path' => '/absolute/path',
        ],
    ];

    $loader = new CaConfigurationLoader;
    $config = $loader->load($input, '/unused');

    $persister = new CaConfigurationPersister;
    $output = $persister->toArray($config);

    expect($output)->toBe($input);
});
