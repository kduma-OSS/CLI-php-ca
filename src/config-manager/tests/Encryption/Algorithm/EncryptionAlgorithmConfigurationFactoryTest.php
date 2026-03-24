<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\AesAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\EncryptionAlgorithmConfigurationFactory;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\SecretBoxAlgorithmConfiguration;

it('discovers all encryption algorithm types', function () {
    $factory = new EncryptionAlgorithmConfigurationFactory;
    $types = $factory->getTypes();

    expect($types)->toHaveKeys(['secret-box', 'sealed-box', 'aes', 'rsa']);
});

it('creates SecretBox from array', function () {
    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'secret-box',
        'id' => 'master',
        'key' => ['type' => 'string', 'value' => str_repeat('a', 32)],
    ], '/base');

    expect($algo)->toBeInstanceOf(SecretBoxAlgorithmConfiguration::class)
        ->and($algo->id)->toBe('master');
});

it('creates AES from array', function () {
    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'aes',
        'id' => 'aes-key',
        'key' => ['type' => 'string', 'value' => str_repeat('a', 32)],
        'mode' => 'cbc',
    ], '/base');

    expect($algo)->toBeInstanceOf(AesAlgorithmConfiguration::class)
        ->and($algo->mode)->toBe('cbc');
});

it('round-trips SecretBox configuration', function () {
    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'secret-box',
        'id' => 'master',
        'key' => 'my_key',
    ], '/base');

    expect($algo->toArray())->toBe([
        'type' => 'secret-box',
        'id' => 'master',
        'key' => 'my_key',
    ]);
});

it('throws on unknown encryption algorithm type', function () {
    $factory = new EncryptionAlgorithmConfigurationFactory;
    $factory->fromArray(['type' => 'chacha20', 'id' => 'x'], '/base');
})->throws(InvalidArgumentException::class, 'Unknown encryption algorithm type "chacha20"');
