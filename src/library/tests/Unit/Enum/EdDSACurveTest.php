<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;

test('length() returns 256 for Ed25519', function () {
    expect(EdDSACurve::Ed25519->length())->toBe(256);
});

test('length() returns 448 for Ed448', function () {
    expect(EdDSACurve::Ed448->length())->toBe(448);
});

test('string values match expected curve names', function () {
    expect(EdDSACurve::Ed25519->value)->toBe('Ed25519')
        ->and(EdDSACurve::Ed448->value)->toBe('Ed448');
});

test('from() creates correct instances from string values', function () {
    expect(EdDSACurve::from('Ed25519'))->toBe(EdDSACurve::Ed25519)
        ->and(EdDSACurve::from('Ed448'))->toBe(EdDSACurve::Ed448);
});

test('from() throws ValueError for invalid curve name', function () {
    EdDSACurve::from('Ed999');
})->throws(ValueError::class);
