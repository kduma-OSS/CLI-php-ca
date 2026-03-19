<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Converter\CertificateSubjectConverter;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;

test('fromStorage/toStorage round-trip', function () {
    $converter = new CertificateSubjectConverter();
    $data = [
        ['type' => 'CN', 'value' => 'example.com'],
        ['type' => 'O', 'value' => 'Example Inc'],
        ['type' => 'C', 'value' => 'US'],
    ];

    $subject = $converter->fromStorage($data);

    expect($subject)->toBeInstanceOf(CertificateSubject::class)
        ->and($converter->toStorage($subject))->toBe($data);
});

test('fromStorage() returns null for non-array input', function () {
    $converter = new CertificateSubjectConverter();

    expect($converter->fromStorage(null))->toBeNull()
        ->and($converter->fromStorage('string'))->toBeNull();
});

test('toStorage() returns value unchanged if not a CertificateSubject', function () {
    $converter = new CertificateSubjectConverter();

    expect($converter->toStorage(null))->toBeNull()
        ->and($converter->toStorage('string'))->toBe('string');
});

test('fromStorage/toStorage round-trip with multiple DN types', function () {
    $converter = new CertificateSubjectConverter();
    $data = [
        ['type' => 'CN', 'value' => 'My CA'],
        ['type' => 'O', 'value' => 'Organization'],
        ['type' => 'OU', 'value' => 'Security'],
        ['type' => 'C', 'value' => 'PL'],
        ['type' => 'ST', 'value' => 'Mazowieckie'],
        ['type' => 'L', 'value' => 'Warsaw'],
    ];

    $subject = $converter->fromStorage($data);
    $result = $converter->toStorage($subject);

    expect($result)->toBe($data);
});
