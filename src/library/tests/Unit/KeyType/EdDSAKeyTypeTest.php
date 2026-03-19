<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;

test('constructor sets curve property', function () {
    $key = new EdDSAKeyType(curve: EdDSACurve::Ed25519);

    expect($key->curve)->toBe(EdDSACurve::Ed25519);
});

test('getType() returns "eddsa"', function () {
    $key = new EdDSAKeyType(curve: EdDSACurve::Ed25519);

    expect($key->getType())->toBe('eddsa');
});

test('toArray() returns correct structure', function () {
    $key = new EdDSAKeyType(curve: EdDSACurve::Ed448);

    expect($key->toArray())->toBe([
        'type' => 'eddsa',
        'curve' => 'Ed448',
    ]);
});

test('fromArray() creates instance from array', function () {
    $key = EdDSAKeyType::fromArray(['type' => 'eddsa', 'curve' => 'Ed25519']);

    expect($key)->toBeInstanceOf(EdDSAKeyType::class)
        ->and($key->curve)->toBe(EdDSACurve::Ed25519);
});

test('toArray/fromArray round-trip preserves data', function () {
    $original = new EdDSAKeyType(curve: EdDSACurve::Ed448);
    $restored = EdDSAKeyType::fromArray($original->toArray());

    expect($restored->toArray())->toBe($original->toArray());
});
