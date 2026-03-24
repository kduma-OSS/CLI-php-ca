<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;
use KDuma\PhpCA\Record\Extension\Extensions\ExtKeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Extensions\KeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Extensions\SubjectAltNameExtension;

beforeEach(function () {
    ExtensionRegistry::reset();
});

test('registerDefaults populates registry with all default extensions', function () {
    ExtensionRegistry::registerDefaults();
    $types = ExtensionRegistry::getTypes();

    expect($types)->toHaveKey('basic-constraints')
        ->and($types)->toHaveKey('key-usage')
        ->and($types)->toHaveKey('ext-key-usage')
        ->and($types)->toHaveKey('subject-alt-name')
        ->and($types)->toHaveKey('crl-distribution-points')
        ->and($types)->toHaveKey('authority-info-access')
        ->and($types)->toHaveKey('subject-key-identifier')
        ->and($types)->toHaveKey('authority-key-identifier')
        ->and($types)->toHaveKey('private-key-usage-period')
        ->and($types)->toHaveKey('netscape-comment')
        ->and($types)->toHaveCount(count($types));
});

test('registerDefaults is idempotent', function () {
    ExtensionRegistry::registerDefaults();
    $first = ExtensionRegistry::getTypes();

    ExtensionRegistry::registerDefaults();
    $second = ExtensionRegistry::getTypes();

    expect($first)->toBe($second);
});

test('register adds a custom extension', function () {
    ExtensionRegistry::register(BasicConstraintsExtension::class);

    $types = ExtensionRegistry::getTypes();

    expect($types)->toHaveCount(1)
        ->and($types['basic-constraints'])->toBe(BasicConstraintsExtension::class);
});

test('resolve returns correct class for known name', function () {
    ExtensionRegistry::registerDefaults();

    expect(ExtensionRegistry::resolve('basic-constraints'))->toBe(BasicConstraintsExtension::class)
        ->and(ExtensionRegistry::resolve('key-usage'))->toBe(KeyUsageExtension::class)
        ->and(ExtensionRegistry::resolve('ext-key-usage'))->toBe(ExtKeyUsageExtension::class)
        ->and(ExtensionRegistry::resolve('subject-alt-name'))->toBe(SubjectAltNameExtension::class);
});

test('resolve throws LogicException for unknown name', function () {
    ExtensionRegistry::registerDefaults();

    ExtensionRegistry::resolve('unknown-extension');
})->throws(LogicException::class, 'Unknown extension type: unknown-extension');

test('reset clears all registered types', function () {
    ExtensionRegistry::registerDefaults();
    expect(ExtensionRegistry::getTypes())->not->toBeEmpty();

    ExtensionRegistry::reset();
    expect(ExtensionRegistry::getTypes())->toBeEmpty();
});

test('getTypes returns empty array before any registration', function () {
    expect(ExtensionRegistry::getTypes())->toBe([]);
});

test('resolve and fromArray integration: creates correct extension from array data', function () {
    ExtensionRegistry::registerDefaults();

    $data = [
        'name' => 'basic-constraints',
        'ca' => true,
        'path_len_constraint' => 2,
        'critical' => true,
    ];

    $class = ExtensionRegistry::resolve($data['name']);
    $extension = $class::fromArray($data);

    expect($extension)->toBeInstanceOf(BasicConstraintsExtension::class)
        ->and($extension->ca)->toBeTrue()
        ->and($extension->pathLenConstraint)->toBe(2);
});
