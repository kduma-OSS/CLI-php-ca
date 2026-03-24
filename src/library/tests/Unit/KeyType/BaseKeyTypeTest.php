<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\KeyType\BaseKeyType;
use KDuma\PhpCA\Record\KeyType\DSAKeyType;
use KDuma\PhpCA\Record\KeyType\ECDSAKeyType;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\DsaParameterSize;
use KDuma\PhpCA\Record\KeyType\Enum\EcCurve;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;
use KDuma\PhpCA\Record\KeyType\RSAKeyType;

test('fromArray() dispatches to RSAKeyType for type "rsa"', function () {
    $key = BaseKeyType::fromArray(['type' => 'rsa', 'size' => 2048]);

    expect($key)->toBeInstanceOf(RSAKeyType::class)
        ->and($key->size)->toBe(2048);
});

test('fromArray() dispatches to DSAKeyType for type "dsa"', function () {
    $key = BaseKeyType::fromArray(['type' => 'dsa', 'parameters' => '2048-256']);

    expect($key)->toBeInstanceOf(DSAKeyType::class)
        ->and($key->parameters)->toBe(DsaParameterSize::L2048_N256);
});

test('fromArray() dispatches to ECDSAKeyType for type "ecdsa"', function () {
    $key = BaseKeyType::fromArray(['type' => 'ecdsa', 'curve' => 'secp256r1']);

    expect($key)->toBeInstanceOf(ECDSAKeyType::class)
        ->and($key->curve)->toBe(EcCurve::Secp256r1);
});

test('fromArray() dispatches to EdDSAKeyType for type "eddsa"', function () {
    $key = BaseKeyType::fromArray(['type' => 'eddsa', 'curve' => 'Ed25519']);

    expect($key)->toBeInstanceOf(EdDSAKeyType::class)
        ->and($key->curve)->toBe(EdDSACurve::Ed25519);
});

test('fromArray() throws InvalidArgumentException for unknown type', function () {
    BaseKeyType::fromArray(['type' => 'unknown']);
})->throws(InvalidArgumentException::class, 'Unknown key type: unknown');
