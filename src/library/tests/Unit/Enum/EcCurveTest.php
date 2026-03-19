<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\KeyType\Enum\EcCurve;

test('length() returns correct value for NIST/SECG prime curves', function (EcCurve $curve, int $expectedLength) {
    expect($curve->length())->toBe($expectedLength);
})->with([
    'secp112r1' => [EcCurve::Secp112r1, 112],
    'secp128r1' => [EcCurve::Secp128r1, 128],
    'secp160k1' => [EcCurve::Secp160k1, 160],
    'secp192r1' => [EcCurve::Secp192r1, 192],
    'secp224r1' => [EcCurve::Secp224r1, 224],
    'secp256r1 (P-256)' => [EcCurve::Secp256r1, 256],
    'secp384r1 (P-384)' => [EcCurve::Secp384r1, 384],
    'secp521r1 (P-521)' => [EcCurve::Secp521r1, 521],
]);

test('length() returns correct value for Brainpool curves', function (EcCurve $curve, int $expectedLength) {
    expect($curve->length())->toBe($expectedLength);
})->with([
    'brainpoolP160r1' => [EcCurve::BrainpoolP160r1, 160],
    'brainpoolP256r1' => [EcCurve::BrainpoolP256r1, 256],
    'brainpoolP384r1' => [EcCurve::BrainpoolP384r1, 384],
    'brainpoolP512r1' => [EcCurve::BrainpoolP512r1, 512],
]);

test('length() returns correct value for binary curves', function (EcCurve $curve, int $expectedLength) {
    expect($curve->length())->toBe($expectedLength);
})->with([
    'sect163k1' => [EcCurve::Sect163k1, 163],
    'sect233r1' => [EcCurve::Sect233r1, 233],
    'sect283k1' => [EcCurve::Sect283k1, 283],
    'sect409r1' => [EcCurve::Sect409r1, 409],
    'sect571r1' => [EcCurve::Sect571r1, 571],
]);

test('length() returns correct value for X9.62 prime curves', function (EcCurve $curve, int $expectedLength) {
    expect($curve->length())->toBe($expectedLength);
})->with([
    'prime192v2' => [EcCurve::Prime192v2, 192],
    'prime239v1' => [EcCurve::Prime239v1, 239],
]);

test('string values match expected curve names', function () {
    expect(EcCurve::Secp256r1->value)->toBe('secp256r1')
        ->and(EcCurve::Secp384r1->value)->toBe('secp384r1')
        ->and(EcCurve::Secp521r1->value)->toBe('secp521r1')
        ->and(EcCurve::BrainpoolP256r1->value)->toBe('brainpoolP256r1');
});
