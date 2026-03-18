<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Integrity\Signer\DsaSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\EcSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\Ed25519SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha1SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha256SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha512SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\RsaSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\SignerConfigurationFactory;

it('discovers all signer types', function () {
    $factory = new SignerConfigurationFactory();
    $types = $factory->getTypes();

    expect($types)->toHaveKeys(['hmac-sha1', 'hmac-sha256', 'hmac-sha512', 'ed25519', 'rsa', 'ec', 'dsa']);
});

it('creates HMAC signer from array', function (string $type, string $expectedClass) {
    $factory = new SignerConfigurationFactory();
    $signer = $factory->fromArray([
        'type' => $type,
        'id' => 'test-signer',
        'secret' => ['type' => 'string', 'value' => 'my_secret'],
    ], '/base');

    expect($signer)->toBeInstanceOf($expectedClass);
})->with([
    ['hmac-sha1', HmacSha1SignerConfiguration::class],
    ['hmac-sha256', HmacSha256SignerConfiguration::class],
    ['hmac-sha512', HmacSha512SignerConfiguration::class],
]);

it('creates Ed25519 signer from array', function () {
    $factory = new SignerConfigurationFactory();
    $signer = $factory->fromArray([
        'type' => 'ed25519',
        'id' => 'ed-signer',
        'public_key' => ['type' => 'base64', 'value' => base64_encode(str_repeat('a', 32))],
        'secret_key' => ['type' => 'base64', 'value' => base64_encode(str_repeat('b', 64))],
    ], '/base');

    expect($signer)->toBeInstanceOf(Ed25519SignerConfiguration::class);
});

it('round-trips HMAC signer', function () {
    $factory = new SignerConfigurationFactory();
    $signer = $factory->fromArray([
        'type' => 'hmac-sha256',
        'id' => 'test',
        'secret' => ['type' => 'string', 'value' => 'key'],
    ], '/base');

    expect($signer->toArray())->toBe([
        'type' => 'hmac-sha256',
        'id' => 'test',
        'secret' => 'key',
    ]);
});

it('round-trips HMAC signer with string shorthand', function () {
    $factory = new SignerConfigurationFactory();
    $signer = $factory->fromArray([
        'type' => 'hmac-sha256',
        'id' => 'test',
        'secret' => 'key',
    ], '/base');

    expect($signer->toArray())->toBe([
        'type' => 'hmac-sha256',
        'id' => 'test',
        'secret' => 'key',
    ]);
});

it('throws on unknown signer type', function () {
    $factory = new SignerConfigurationFactory();
    $factory->fromArray(['type' => 'chacha20'], '/base');
})->throws(InvalidArgumentException::class, 'Unknown signer type "chacha20"');
