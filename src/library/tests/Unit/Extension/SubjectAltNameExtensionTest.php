<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Extension\Extensions\SubjectAltNameExtension;

test('DNS names', function () {
    $ext = new SubjectAltNameExtension(dnsNames: ['example.com', '*.example.com']);

    expect($ext->dnsNames)->toBe(['example.com', '*.example.com'])
        ->and($ext->ipAddresses)->toBe([])
        ->and($ext->emails)->toBe([])
        ->and($ext->uris)->toBe([]);
});

test('IP addresses', function () {
    $ext = new SubjectAltNameExtension(ipAddresses: ['192.168.1.1', '10.0.0.1']);

    expect($ext->ipAddresses)->toBe(['192.168.1.1', '10.0.0.1'])
        ->and($ext->dnsNames)->toBe([]);
});

test('email addresses', function () {
    $ext = new SubjectAltNameExtension(emails: ['admin@example.com', 'info@example.com']);

    expect($ext->emails)->toBe(['admin@example.com', 'info@example.com']);
});

test('URIs', function () {
    $ext = new SubjectAltNameExtension(uris: ['https://example.com']);

    expect($ext->uris)->toBe(['https://example.com']);
});

test('mixed SAN types', function () {
    $ext = new SubjectAltNameExtension(
        dnsNames: ['example.com'],
        ipAddresses: ['192.168.1.1'],
        emails: ['admin@example.com'],
        uris: ['https://example.com'],
    );

    expect($ext->dnsNames)->toHaveCount(1)
        ->and($ext->ipAddresses)->toHaveCount(1)
        ->and($ext->emails)->toHaveCount(1)
        ->and($ext->uris)->toHaveCount(1);
});

test('isCritical defaults to false', function () {
    $ext = new SubjectAltNameExtension;

    expect($ext->isCritical())->toBeFalse();
});

test('isCritical can be set to true', function () {
    $ext = new SubjectAltNameExtension(critical: true);

    expect($ext->isCritical())->toBeTrue();
});

test('oid returns correct OID', function () {
    expect(SubjectAltNameExtension::oid())->toBe('2.5.29.17');
});

test('name returns correct name', function () {
    expect(SubjectAltNameExtension::name())->toBe('subject-alt-name');
});

test('toArray includes all fields', function () {
    $ext = new SubjectAltNameExtension(
        dnsNames: ['example.com'],
        ipAddresses: ['10.0.0.1'],
        emails: ['test@example.com'],
        uris: ['https://example.com'],
        critical: true,
    );

    expect($ext->toArray())->toBe([
        'name' => 'subject-alt-name',
        'critical' => true,
        'dns_names' => ['example.com'],
        'ip_addresses' => ['10.0.0.1'],
        'emails' => ['test@example.com'],
        'uris' => ['https://example.com'],
    ]);
});

test('fromArray round-trip', function () {
    $original = new SubjectAltNameExtension(
        dnsNames: ['example.com', '*.example.com'],
        ipAddresses: ['192.168.1.1'],
        emails: ['admin@example.com'],
        uris: ['https://example.com/ca'],
        critical: false,
    );
    $array = $original->toArray();
    $restored = SubjectAltNameExtension::fromArray($array);

    expect($restored->dnsNames)->toBe($original->dnsNames)
        ->and($restored->ipAddresses)->toBe($original->ipAddresses)
        ->and($restored->emails)->toBe($original->emails)
        ->and($restored->uris)->toBe($original->uris)
        ->and($restored->isCritical())->toBe($original->isCritical());
});

test('fromArray with empty data uses defaults', function () {
    $ext = SubjectAltNameExtension::fromArray([]);

    expect($ext->dnsNames)->toBe([])
        ->and($ext->ipAddresses)->toBe([])
        ->and($ext->emails)->toBe([])
        ->and($ext->uris)->toBe([])
        ->and($ext->isCritical())->toBeFalse();
});
