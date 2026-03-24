<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Converter\ExtensionsConverter;
use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;
use KDuma\PhpCA\Record\Extension\Extensions\KeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Extensions\SubjectKeyIdentifierExtension;

beforeEach(function () {
    ExtensionRegistry::reset();
    ExtensionRegistry::registerDefaults();
});

test('fromStorage/toStorage round-trip with BasicConstraints extension', function () {
    $converter = new ExtensionsConverter;
    $data = [
        [
            'name' => 'basic-constraints',
            'critical' => true,
            'ca' => true,
            'path_len_constraint' => 0,
        ],
    ];

    $extensions = $converter->fromStorage($data);

    expect($extensions)->toHaveCount(1)
        ->and($extensions[0])->toBeInstanceOf(BasicConstraintsExtension::class)
        ->and($extensions[0]->ca)->toBeTrue()
        ->and($extensions[0]->pathLenConstraint)->toBe(0)
        ->and($extensions[0]->isCritical())->toBeTrue();

    $result = $converter->toStorage($extensions);

    expect($result)->toBe($data);
});

test('fromStorage/toStorage round-trip with KeyUsage extension', function () {
    $converter = new ExtensionsConverter;
    $data = [
        [
            'name' => 'key-usage',
            'critical' => true,
            'digital_signature' => true,
            'non_repudiation' => false,
            'key_encipherment' => true,
            'data_encipherment' => false,
            'key_agreement' => false,
            'key_cert_sign' => true,
            'crl_sign' => true,
            'encipher_only' => false,
            'decipher_only' => false,
        ],
    ];

    $extensions = $converter->fromStorage($data);

    expect($extensions)->toHaveCount(1)
        ->and($extensions[0])->toBeInstanceOf(KeyUsageExtension::class)
        ->and($extensions[0]->digitalSignature)->toBeTrue()
        ->and($extensions[0]->keyCertSign)->toBeTrue()
        ->and($extensions[0]->cRLSign)->toBeTrue()
        ->and($extensions[0]->keyEncipherment)->toBeTrue();

    $result = $converter->toStorage($extensions);

    expect($result)->toBe($data);
});

test('fromStorage/toStorage round-trip with multiple extensions', function () {
    $converter = new ExtensionsConverter;
    $data = [
        [
            'name' => 'basic-constraints',
            'critical' => true,
            'ca' => false,
        ],
        [
            'name' => 'subject-key-identifier',
            'critical' => false,
            'key_identifier' => 'abc123def456',
        ],
    ];

    $extensions = $converter->fromStorage($data);

    expect($extensions)->toHaveCount(2)
        ->and($extensions[0])->toBeInstanceOf(BasicConstraintsExtension::class)
        ->and($extensions[1])->toBeInstanceOf(SubjectKeyIdentifierExtension::class)
        ->and($extensions[1]->keyIdentifier)->toBe('abc123def456');

    $result = $converter->toStorage($extensions);

    expect($result)->toBe($data);
});

test('fromStorage() returns empty array for non-array input', function () {
    $converter = new ExtensionsConverter;

    expect($converter->fromStorage(null))->toBe([])
        ->and($converter->fromStorage('string'))->toBe([]);
});

test('toStorage() returns empty array for non-array input', function () {
    $converter = new ExtensionsConverter;

    expect($converter->toStorage(null))->toBe([])
        ->and($converter->toStorage('string'))->toBe([]);
});

test('fromStorage() skips entries without name key', function () {
    $converter = new ExtensionsConverter;
    $data = [
        [
            'name' => 'basic-constraints',
            'critical' => true,
            'ca' => true,
        ],
        [
            'critical' => true,
            'ca' => false,
        ],
        'not-an-array',
    ];

    $extensions = $converter->fromStorage($data);

    expect($extensions)->toHaveCount(1)
        ->and($extensions[0])->toBeInstanceOf(BasicConstraintsExtension::class);
});
