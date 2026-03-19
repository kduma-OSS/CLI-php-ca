<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\KeyType\ECDSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\EcCurve;

test('constructor sets curve property', function () {
    $key = new ECDSAKeyType(curve: EcCurve::Secp256r1);

    expect($key->curve)->toBe(EcCurve::Secp256r1);
});

test('getType() returns "ecdsa"', function () {
    $key = new ECDSAKeyType(curve: EcCurve::Secp384r1);

    expect($key->getType())->toBe('ecdsa');
});

test('toArray() returns correct structure', function () {
    $key = new ECDSAKeyType(curve: EcCurve::Secp521r1);

    expect($key->toArray())->toBe([
        'type' => 'ecdsa',
        'curve' => 'secp521r1',
    ]);
});

test('fromArray() creates instance from array', function () {
    $key = ECDSAKeyType::fromArray(['type' => 'ecdsa', 'curve' => 'secp256r1']);

    expect($key)->toBeInstanceOf(ECDSAKeyType::class)
        ->and($key->curve)->toBe(EcCurve::Secp256r1);
});

test('toArray/fromArray round-trip preserves data', function () {
    $original = new ECDSAKeyType(curve: EcCurve::BrainpoolP384r1);
    $restored = ECDSAKeyType::fromArray($original->toArray());

    expect($restored->toArray())->toBe($original->toArray());
});
