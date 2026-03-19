<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\CertificateValidity;

test('isValid() returns true when current date is within validity period', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('-1 year'),
        notAfter: new DateTimeImmutable('+1 year'),
    );

    expect($validity->isValid())->toBeTrue();
});

test('isValid() returns true when checked at exact notBefore boundary', function () {
    $now = new DateTimeImmutable('2025-06-15T12:00:00Z');

    $validity = new CertificateValidity(
        notBefore: $now,
        notAfter: new DateTimeImmutable('2026-06-15T12:00:00Z'),
    );

    expect($validity->isValid($now))->toBeTrue();
});

test('isValid() returns true when checked at exact notAfter boundary', function () {
    $now = new DateTimeImmutable('2026-06-15T12:00:00Z');

    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('2025-06-15T12:00:00Z'),
        notAfter: $now,
    );

    expect($validity->isValid($now))->toBeTrue();
});

test('isValid() returns false for expired certificate', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('2020-01-01'),
        notAfter: new DateTimeImmutable('2021-01-01'),
    );

    expect($validity->isValid())->toBeFalse();
});

test('isValid() returns false for not-yet-valid certificate', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('+1 year'),
        notAfter: new DateTimeImmutable('+2 years'),
    );

    expect($validity->isValid())->toBeFalse();
});

test('isValid() accepts a custom date for checking', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('2025-01-01'),
        notAfter: new DateTimeImmutable('2025-12-31'),
    );

    expect($validity->isValid(new DateTimeImmutable('2025-06-15')))->toBeTrue()
        ->and($validity->isValid(new DateTimeImmutable('2024-06-15')))->toBeFalse()
        ->and($validity->isValid(new DateTimeImmutable('2026-06-15')))->toBeFalse();
});

test('isExpired() returns true when current date is past notAfter', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('2020-01-01'),
        notAfter: new DateTimeImmutable('2021-01-01'),
    );

    expect($validity->isExpired())->toBeTrue();
});

test('isExpired() returns false when certificate is still valid', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('-1 year'),
        notAfter: new DateTimeImmutable('+1 year'),
    );

    expect($validity->isExpired())->toBeFalse();
});

test('isExpired() returns false for not-yet-valid certificate', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('+1 year'),
        notAfter: new DateTimeImmutable('+2 years'),
    );

    expect($validity->isExpired())->toBeFalse();
});

test('isExpired() accepts a custom date for checking', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('2025-01-01'),
        notAfter: new DateTimeImmutable('2025-12-31'),
    );

    expect($validity->isExpired(new DateTimeImmutable('2025-06-15')))->toBeFalse()
        ->and($validity->isExpired(new DateTimeImmutable('2026-06-15')))->toBeTrue();
});

test('duration() returns correct DateInterval', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('2025-01-01'),
        notAfter: new DateTimeImmutable('2026-01-01'),
    );

    $duration = $validity->duration();

    expect($duration)->toBeInstanceOf(DateInterval::class)
        ->and($duration->y)->toBe(1)
        ->and($duration->m)->toBe(0)
        ->and($duration->d)->toBe(0);
});

test('duration() returns correct interval for multi-year period', function () {
    $validity = new CertificateValidity(
        notBefore: new DateTimeImmutable('2025-01-01'),
        notAfter: new DateTimeImmutable('2027-07-01'),
    );

    $duration = $validity->duration();

    expect($duration->y)->toBe(2)
        ->and($duration->m)->toBe(6);
});
