<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Extension\Extensions\KeyUsageExtension;

test('individual usage flag digitalSignature', function () {
    $ext = new KeyUsageExtension(digitalSignature: true);

    expect($ext->digitalSignature)->toBeTrue()
        ->and($ext->nonRepudiation)->toBeFalse()
        ->and($ext->keyEncipherment)->toBeFalse()
        ->and($ext->dataEncipherment)->toBeFalse()
        ->and($ext->keyAgreement)->toBeFalse()
        ->and($ext->keyCertSign)->toBeFalse()
        ->and($ext->cRLSign)->toBeFalse()
        ->and($ext->encipherOnly)->toBeFalse()
        ->and($ext->decipherOnly)->toBeFalse();
});

test('individual usage flag keyCertSign', function () {
    $ext = new KeyUsageExtension(keyCertSign: true);

    expect($ext->keyCertSign)->toBeTrue()
        ->and($ext->digitalSignature)->toBeFalse();
});

test('multiple usages combined', function () {
    $ext = new KeyUsageExtension(
        digitalSignature: true,
        keyEncipherment: true,
        keyCertSign: true,
        cRLSign: true,
    );

    expect($ext->digitalSignature)->toBeTrue()
        ->and($ext->keyEncipherment)->toBeTrue()
        ->and($ext->keyCertSign)->toBeTrue()
        ->and($ext->cRLSign)->toBeTrue()
        ->and($ext->nonRepudiation)->toBeFalse()
        ->and($ext->dataEncipherment)->toBeFalse();
});

test('isCritical defaults to true', function () {
    $ext = new KeyUsageExtension;

    expect($ext->isCritical())->toBeTrue();
});

test('isCritical can be set to false', function () {
    $ext = new KeyUsageExtension(critical: false);

    expect($ext->isCritical())->toBeFalse();
});

test('oid returns correct OID', function () {
    expect(KeyUsageExtension::oid())->toBe('2.5.29.15');
});

test('name returns correct name', function () {
    expect(KeyUsageExtension::name())->toBe('key-usage');
});

test('toArray includes all flags', function () {
    $ext = new KeyUsageExtension(
        digitalSignature: true,
        keyCertSign: true,
        critical: true,
    );
    $array = $ext->toArray();

    expect($array)->toBe([
        'name' => 'key-usage',
        'critical' => true,
        'digital_signature' => true,
        'non_repudiation' => false,
        'key_encipherment' => false,
        'data_encipherment' => false,
        'key_agreement' => false,
        'key_cert_sign' => true,
        'crl_sign' => false,
        'encipher_only' => false,
        'decipher_only' => false,
    ]);
});

test('fromArray round-trip preserves all flags', function () {
    $original = new KeyUsageExtension(
        digitalSignature: true,
        nonRepudiation: true,
        keyEncipherment: false,
        dataEncipherment: false,
        keyAgreement: true,
        keyCertSign: true,
        cRLSign: true,
        encipherOnly: false,
        decipherOnly: true,
        critical: false,
    );
    $array = $original->toArray();
    $restored = KeyUsageExtension::fromArray($array);

    expect($restored->digitalSignature)->toBe($original->digitalSignature)
        ->and($restored->nonRepudiation)->toBe($original->nonRepudiation)
        ->and($restored->keyEncipherment)->toBe($original->keyEncipherment)
        ->and($restored->dataEncipherment)->toBe($original->dataEncipherment)
        ->and($restored->keyAgreement)->toBe($original->keyAgreement)
        ->and($restored->keyCertSign)->toBe($original->keyCertSign)
        ->and($restored->cRLSign)->toBe($original->cRLSign)
        ->and($restored->encipherOnly)->toBe($original->encipherOnly)
        ->and($restored->decipherOnly)->toBe($original->decipherOnly)
        ->and($restored->isCritical())->toBe($original->isCritical());
});

test('fromArray with empty array uses defaults', function () {
    $ext = KeyUsageExtension::fromArray([]);

    expect($ext->digitalSignature)->toBeFalse()
        ->and($ext->nonRepudiation)->toBeFalse()
        ->and($ext->keyEncipherment)->toBeFalse()
        ->and($ext->isCritical())->toBeTrue();
});
