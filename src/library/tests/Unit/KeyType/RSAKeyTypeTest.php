<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\KeyType\RSAKeyType;

test('constructor sets size property', function () {
    $key = new RSAKeyType(size: 4096);

    expect($key->size)->toBe(4096);
});

test('getType() returns "rsa"', function () {
    $key = new RSAKeyType(size: 2048);

    expect($key->getType())->toBe('rsa');
});

test('toArray() returns correct structure', function () {
    $key = new RSAKeyType(size: 2048);

    expect($key->toArray())->toBe([
        'type' => 'rsa',
        'size' => 2048,
    ]);
});

test('fromArray() creates instance from array', function () {
    $key = RSAKeyType::fromArray(['type' => 'rsa', 'size' => 4096]);

    expect($key)->toBeInstanceOf(RSAKeyType::class)
        ->and($key->size)->toBe(4096)
        ->and($key->getType())->toBe('rsa');
});

test('toArray/fromArray round-trip preserves data', function () {
    $original = new RSAKeyType(size: 3072);
    $restored = RSAKeyType::fromArray($original->toArray());

    expect($restored->toArray())->toBe($original->toArray());
});
