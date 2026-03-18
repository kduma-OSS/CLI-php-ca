<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Blake2bHasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Crc32HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\HasherConfigurationFactory;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Md5HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha1HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha3_256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha512HasherConfiguration;

it('discovers all hasher types', function () {
    $factory = new HasherConfigurationFactory();
    $types = $factory->getTypes();

    expect($types)->toHaveKeys(['crc32', 'md5', 'sha1', 'sha256', 'sha3-256', 'sha512', 'blake2b']);
});

it('creates correct hasher from type', function (string $type, string $expectedClass) {
    $factory = new HasherConfigurationFactory();
    $hasher = $factory->fromArray(['type' => $type]);

    expect($hasher)->toBeInstanceOf($expectedClass);
})->with([
    ['crc32', Crc32HasherConfiguration::class],
    ['md5', Md5HasherConfiguration::class],
    ['sha1', Sha1HasherConfiguration::class],
    ['sha256', Sha256HasherConfiguration::class],
    ['sha3-256', Sha3_256HasherConfiguration::class],
    ['sha512', Sha512HasherConfiguration::class],
    ['blake2b', Blake2bHasherConfiguration::class],
]);

it('round-trips hasher configuration', function (string $type) {
    $factory = new HasherConfigurationFactory();
    $hasher = $factory->fromArray(['type' => $type]);

    expect($hasher->toArray())->toBe(['type' => $type]);
})->with(['crc32', 'md5', 'sha1', 'sha256', 'sha3-256', 'sha512', 'blake2b']);

it('throws on unknown hasher type', function () {
    $factory = new HasherConfigurationFactory();
    $factory->fromArray(['type' => 'xxhash']);
})->throws(InvalidArgumentException::class, 'Unknown hasher type "xxhash"');
