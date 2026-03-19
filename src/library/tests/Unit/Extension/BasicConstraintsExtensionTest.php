<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;

test('CA=true with pathLength constraint', function () {
    $ext = new BasicConstraintsExtension(ca: true, pathLenConstraint: 3);

    expect($ext->ca)->toBeTrue()
        ->and($ext->pathLenConstraint)->toBe(3)
        ->and($ext->isCritical())->toBeTrue();
});

test('CA=false with no pathLength constraint', function () {
    $ext = new BasicConstraintsExtension(ca: false);

    expect($ext->ca)->toBeFalse()
        ->and($ext->pathLenConstraint)->toBeNull()
        ->and($ext->isCritical())->toBeTrue();
});

test('isCritical defaults to true and can be overridden', function () {
    $critical = new BasicConstraintsExtension(ca: true);
    $notCritical = new BasicConstraintsExtension(ca: true, critical: false);

    expect($critical->isCritical())->toBeTrue()
        ->and($notCritical->isCritical())->toBeFalse();
});

test('oid returns correct OID', function () {
    expect(BasicConstraintsExtension::oid())->toBe('2.5.29.19');
});

test('name returns correct name', function () {
    expect(BasicConstraintsExtension::name())->toBe('basic-constraints');
});

test('toArray includes all set fields', function () {
    $ext = new BasicConstraintsExtension(ca: true, pathLenConstraint: 2, critical: true);
    $array = $ext->toArray();

    expect($array)->toBe([
        'name' => 'basic-constraints',
        'critical' => true,
        'ca' => true,
        'path_len_constraint' => 2,
    ]);
});

test('toArray filters out null pathLenConstraint', function () {
    $ext = new BasicConstraintsExtension(ca: false, critical: false);
    $array = $ext->toArray();

    expect($array)->toHaveKey('name')
        ->and($array)->toHaveKey('ca')
        ->and($array)->not->toHaveKey('path_len_constraint');
});

test('fromArray round-trip with CA=true and pathLength', function () {
    $original = new BasicConstraintsExtension(ca: true, pathLenConstraint: 5, critical: true);
    $array = $original->toArray();
    $restored = BasicConstraintsExtension::fromArray($array);

    expect($restored->ca)->toBe($original->ca)
        ->and($restored->pathLenConstraint)->toBe($original->pathLenConstraint)
        ->and($restored->isCritical())->toBe($original->isCritical());
});

test('fromArray round-trip with CA=false and no pathLength', function () {
    $original = new BasicConstraintsExtension(ca: false, critical: false);
    $array = $original->toArray();
    $restored = BasicConstraintsExtension::fromArray($array);

    expect($restored->ca)->toBe($original->ca)
        ->and($restored->pathLenConstraint)->toBe($original->pathLenConstraint)
        ->and($restored->isCritical())->toBe($original->isCritical());
});

test('fromArray with defaults when keys are missing', function () {
    $ext = BasicConstraintsExtension::fromArray([]);

    expect($ext->ca)->toBeFalse()
        ->and($ext->pathLenConstraint)->toBeNull()
        ->and($ext->isCritical())->toBeTrue();
});
