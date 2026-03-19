<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Extension\Extensions\ExtKeyUsageExtension;

test('server auth usage', function () {
    $ext = new ExtKeyUsageExtension(usages: ['serverAuth']);

    expect($ext->usages)->toBe(['serverAuth']);
});

test('client auth usage', function () {
    $ext = new ExtKeyUsageExtension(usages: ['clientAuth']);

    expect($ext->usages)->toBe(['clientAuth']);
});

test('multiple usages', function () {
    $ext = new ExtKeyUsageExtension(usages: ['serverAuth', 'clientAuth', 'codeSigning', 'emailProtection']);

    expect($ext->usages)->toHaveCount(4)
        ->and($ext->usages)->toBe(['serverAuth', 'clientAuth', 'codeSigning', 'emailProtection']);
});

test('isCritical defaults to false', function () {
    $ext = new ExtKeyUsageExtension(usages: ['serverAuth']);

    expect($ext->isCritical())->toBeFalse();
});

test('isCritical can be set to true', function () {
    $ext = new ExtKeyUsageExtension(usages: ['serverAuth'], critical: true);

    expect($ext->isCritical())->toBeTrue();
});

test('oid returns correct OID', function () {
    expect(ExtKeyUsageExtension::oid())->toBe('2.5.29.37');
});

test('name returns correct name', function () {
    expect(ExtKeyUsageExtension::name())->toBe('ext-key-usage');
});

test('toArray includes usages and critical', function () {
    $ext = new ExtKeyUsageExtension(usages: ['serverAuth', 'clientAuth'], critical: true);

    expect($ext->toArray())->toBe([
        'name' => 'ext-key-usage',
        'critical' => true,
        'usages' => ['serverAuth', 'clientAuth'],
    ]);
});

test('fromArray round-trip', function () {
    $original = new ExtKeyUsageExtension(
        usages: ['serverAuth', 'clientAuth', 'OCSPSigning', 'timeStamping'],
        critical: true,
    );
    $array = $original->toArray();
    $restored = ExtKeyUsageExtension::fromArray($array);

    expect($restored->usages)->toBe($original->usages)
        ->and($restored->isCritical())->toBe($original->isCritical());
});

test('fromArray with empty data uses defaults', function () {
    $ext = ExtKeyUsageExtension::fromArray([]);

    expect($ext->usages)->toBe([])
        ->and($ext->isCritical())->toBeFalse();
});

test('fromArray round-trip with empty usages', function () {
    $original = new ExtKeyUsageExtension(usages: []);
    $array = $original->toArray();
    $restored = ExtKeyUsageExtension::fromArray($array);

    expect($restored->usages)->toBe([]);
});
