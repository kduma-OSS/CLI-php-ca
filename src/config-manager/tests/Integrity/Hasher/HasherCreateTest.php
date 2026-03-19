<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Blake2bHasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Crc32HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Md5HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha1HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha3_256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha512HasherConfiguration;
use KDuma\SimpleDAL\Integrity\Contracts\HashingAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Crc32HashingAlgorithm;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Md5HashingAlgorithm;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Sha1HashingAlgorithm;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Sha256HashingAlgorithm;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Sha3_256HashingAlgorithm;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Sha512HashingAlgorithm;
use KDuma\SimpleDAL\Integrity\Sodium\Blake2bHashingAlgorithm;

test('createHasher returns correct implementation', function (string $configClass, string $expectedClass) {
    $config = new $configClass();
    $hasher = $config->createHasher();

    expect($hasher)->toBeInstanceOf(HashingAlgorithmInterface::class)
        ->and($hasher)->toBeInstanceOf($expectedClass);
})->with([
    'crc32' => [Crc32HasherConfiguration::class, Crc32HashingAlgorithm::class],
    'md5' => [Md5HasherConfiguration::class, Md5HashingAlgorithm::class],
    'sha1' => [Sha1HasherConfiguration::class, Sha1HashingAlgorithm::class],
    'sha256' => [Sha256HasherConfiguration::class, Sha256HashingAlgorithm::class],
    'sha3-256' => [Sha3_256HasherConfiguration::class, Sha3_256HashingAlgorithm::class],
    'sha512' => [Sha512HasherConfiguration::class, Sha512HashingAlgorithm::class],
    'blake2b' => [Blake2bHasherConfiguration::class, Blake2bHashingAlgorithm::class],
]);

test('createHasher produces a functional hasher', function (string $configClass) {
    $config = new $configClass();
    $hasher = $config->createHasher();

    $hash = $hasher->hash('test data');

    expect($hash)->toBeString()->not->toBeEmpty();
})->with([
    'crc32' => [Crc32HasherConfiguration::class],
    'md5' => [Md5HasherConfiguration::class],
    'sha1' => [Sha1HasherConfiguration::class],
    'sha256' => [Sha256HasherConfiguration::class],
    'sha3-256' => [Sha3_256HasherConfiguration::class],
    'sha512' => [Sha512HasherConfiguration::class],
    'blake2b' => [Blake2bHasherConfiguration::class],
]);
