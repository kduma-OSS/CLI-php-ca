<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Converter\KeyTypeConverter;
use KDuma\PhpCA\Record\KeyType\DSAKeyType;
use KDuma\PhpCA\Record\KeyType\ECDSAKeyType;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\RSAKeyType;

test('fromStorage/toStorage round-trip for RSA key type', function () {
    $converter = new KeyTypeConverter;
    $data = ['type' => 'rsa', 'size' => 4096];

    $keyType = $converter->fromStorage($data);

    expect($keyType)->toBeInstanceOf(RSAKeyType::class)
        ->and($converter->toStorage($keyType))->toBe($data);
});

test('fromStorage/toStorage round-trip for DSA key type', function () {
    $converter = new KeyTypeConverter;
    $data = ['type' => 'dsa', 'parameters' => '2048-256'];

    $keyType = $converter->fromStorage($data);

    expect($keyType)->toBeInstanceOf(DSAKeyType::class)
        ->and($converter->toStorage($keyType))->toBe($data);
});

test('fromStorage/toStorage round-trip for ECDSA key type', function () {
    $converter = new KeyTypeConverter;
    $data = ['type' => 'ecdsa', 'curve' => 'secp384r1'];

    $keyType = $converter->fromStorage($data);

    expect($keyType)->toBeInstanceOf(ECDSAKeyType::class)
        ->and($converter->toStorage($keyType))->toBe($data);
});

test('fromStorage/toStorage round-trip for EdDSA key type', function () {
    $converter = new KeyTypeConverter;
    $data = ['type' => 'eddsa', 'curve' => 'Ed25519'];

    $keyType = $converter->fromStorage($data);

    expect($keyType)->toBeInstanceOf(EdDSAKeyType::class)
        ->and($converter->toStorage($keyType))->toBe($data);
});
