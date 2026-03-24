<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\CertificateValidity;
use KDuma\PhpCA\Record\Converter\CertificateValidityConverter;

test('fromStorage/toStorage round-trip', function () {
    $converter = new CertificateValidityConverter;
    $data = [
        'not_before' => '2025-01-01T00:00:00+00:00',
        'not_after' => '2026-01-01T00:00:00+00:00',
    ];

    $validity = $converter->fromStorage($data);

    expect($validity)->toBeInstanceOf(CertificateValidity::class)
        ->and($validity->notBefore->format('c'))->toBe('2025-01-01T00:00:00+00:00')
        ->and($validity->notAfter->format('c'))->toBe('2026-01-01T00:00:00+00:00');

    $result = $converter->toStorage($validity);

    expect($result)->toBe($data);
});

test('fromStorage() returns null for non-array input', function () {
    $converter = new CertificateValidityConverter;

    expect($converter->fromStorage(null))->toBeNull()
        ->and($converter->fromStorage('string'))->toBeNull();
});

test('toStorage() returns value unchanged if not a CertificateValidity', function () {
    $converter = new CertificateValidityConverter;

    expect($converter->toStorage(null))->toBeNull()
        ->and($converter->toStorage('string'))->toBe('string');
});

test('fromStorage() correctly parses ISO 8601 dates with timezone', function () {
    $converter = new CertificateValidityConverter;
    $data = [
        'not_before' => '2025-06-15T10:30:00+02:00',
        'not_after' => '2026-06-15T10:30:00+02:00',
    ];

    $validity = $converter->fromStorage($data);

    expect($validity->notBefore->format('Y-m-d H:i:s'))->toBe('2025-06-15 10:30:00')
        ->and($validity->notAfter->format('Y-m-d H:i:s'))->toBe('2026-06-15 10:30:00');
});
