<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\KeyType\DSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\DsaParameterSize;

test('constructor sets parameters property', function () {
    $key = new DSAKeyType(parameters: DsaParameterSize::L2048_N256);

    expect($key->parameters)->toBe(DsaParameterSize::L2048_N256);
});

test('getType() returns "dsa"', function () {
    $key = new DSAKeyType(parameters: DsaParameterSize::L1024_N160);

    expect($key->getType())->toBe('dsa');
});

test('toArray() returns correct structure', function () {
    $key = new DSAKeyType(parameters: DsaParameterSize::L3072_N256);

    expect($key->toArray())->toBe([
        'type' => 'dsa',
        'parameters' => '3072-256',
    ]);
});

test('fromArray() creates instance from array', function () {
    $key = DSAKeyType::fromArray(['type' => 'dsa', 'parameters' => '2048-224']);

    expect($key)->toBeInstanceOf(DSAKeyType::class)
        ->and($key->parameters)->toBe(DsaParameterSize::L2048_N224);
});

test('toArray/fromArray round-trip preserves data', function () {
    $original = new DSAKeyType(parameters: DsaParameterSize::L2048_N256);
    $restored = DSAKeyType::fromArray($original->toArray());

    expect($restored->toArray())->toBe($original->toArray());
});
