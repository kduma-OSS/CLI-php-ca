<?php

declare(strict_types=1);

use KDuma\PhpCA\Helpers\FingerprintHelper;

test('fromPhpSecLib() converts base64 fingerprint to hex', function () {
    // "Hello" in base64 is "SGVsbG8=", in hex is "48656c6c6f"
    $base64 = base64_encode('Hello');
    $hex = FingerprintHelper::fromPhpSecLib($base64);

    expect($hex)->toBe(bin2hex('Hello'));
});

test('toPhpSecLib() converts hex fingerprint to base64', function () {
    $hex = bin2hex('Hello');
    $base64 = FingerprintHelper::toPhpSecLib($hex);

    expect($base64)->toBe(base64_encode('Hello'));
});

test('fromPhpSecLib() and toPhpSecLib() are inverse operations', function () {
    $originalBase64 = base64_encode(random_bytes(32));

    $hex = FingerprintHelper::fromPhpSecLib($originalBase64);
    $roundTripped = FingerprintHelper::toPhpSecLib($hex);

    expect($roundTripped)->toBe($originalBase64);
});

test('toPhpSecLib() and fromPhpSecLib() round-trip from hex', function () {
    $originalHex = bin2hex(random_bytes(32));

    $base64 = FingerprintHelper::toPhpSecLib($originalHex);
    $roundTripped = FingerprintHelper::fromPhpSecLib($base64);

    expect($roundTripped)->toBe($originalHex);
});

test('fromPhpSecLib() produces lowercase hex output', function () {
    $base64 = base64_encode("\xFF\xAB\xCD");
    $hex = FingerprintHelper::fromPhpSecLib($base64);

    expect($hex)->toBe('ffabcd')
        ->and($hex)->toBe(strtolower($hex));
});
