<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateSubject\DN\CommonName;
use KDuma\PhpCA\Record\CertificateSubject\DN\Organization;
use KDuma\PhpCA\Record\CertificateSubject\DN\Country;
use KDuma\PhpCA\Record\CertificateSubject\DN\State;
use KDuma\PhpCA\Record\CertificateSubject\DN\Locality;
use KDuma\PhpCA\Record\CertificateSubject\DN\OrganizationalUnit;

test('toString() produces correct DN string from components', function () {
    $subject = new CertificateSubject([
        new CommonName('example.com'),
        new Organization('Example Inc'),
        new Country('US'),
    ]);

    expect($subject->toString())->toBe('CN=example.com, O=Example Inc, C=US');
});

test('__toString() delegates to toString()', function () {
    $subject = new CertificateSubject([
        new CommonName('test.com'),
        new Organization('Test Org'),
    ]);

    expect((string) $subject)->toBe('CN=test.com, O=Test Org');
});

test('fromString() parses a DN string into components', function () {
    $subject = CertificateSubject::fromString('CN=example.com, O=Example Inc, C=US');

    expect($subject->components)->toHaveCount(3)
        ->and($subject->components[0])->toBeInstanceOf(CommonName::class)
        ->and($subject->components[0]->value)->toBe('example.com')
        ->and($subject->components[1])->toBeInstanceOf(Organization::class)
        ->and($subject->components[1]->value)->toBe('Example Inc')
        ->and($subject->components[2])->toBeInstanceOf(Country::class)
        ->and($subject->components[2]->value)->toBe('US');
});

test('fromString() handles whitespace variations', function () {
    $subject = CertificateSubject::fromString('CN=test.com,O=Org,C=PL');

    expect($subject->components)->toHaveCount(3)
        ->and($subject->toString())->toBe('CN=test.com, O=Org, C=PL');
});

test('fromString() skips unknown DN types', function () {
    $subject = CertificateSubject::fromString('CN=test.com, UNKNOWN=value, O=Org');

    expect($subject->components)->toHaveCount(2)
        ->and($subject->components[0])->toBeInstanceOf(CommonName::class)
        ->and($subject->components[1])->toBeInstanceOf(Organization::class);
});

test('fromArray() and toArray() round-trip', function () {
    $data = [
        ['type' => 'CN', 'value' => 'example.com'],
        ['type' => 'O', 'value' => 'Example Inc'],
        ['type' => 'C', 'value' => 'US'],
        ['type' => 'ST', 'value' => 'California'],
        ['type' => 'L', 'value' => 'San Francisco'],
    ];

    $subject = CertificateSubject::fromArray($data);

    expect($subject->components)->toHaveCount(5)
        ->and($subject->toArray())->toBe($data);
});

test('fromArray() skips entries with missing type or value', function () {
    $data = [
        ['type' => 'CN', 'value' => 'example.com'],
        ['type' => 'O'],
        ['value' => 'orphan'],
        ['type' => 'C', 'value' => 'US'],
    ];

    $subject = CertificateSubject::fromArray($data);

    expect($subject->components)->toHaveCount(2);
});

test('get() returns all matching components by short name', function () {
    $subject = new CertificateSubject([
        new CommonName('example.com'),
        new Organization('Org A'),
        new OrganizationalUnit('Unit 1'),
        new OrganizationalUnit('Unit 2'),
        new Country('US'),
    ]);

    $ous = $subject->get('OU');

    expect($ous)->toHaveCount(2)
        ->and($ous[0]->value)->toBe('Unit 1')
        ->and($ous[1]->value)->toBe('Unit 2');
});

test('get() returns empty array when no match', function () {
    $subject = new CertificateSubject([
        new CommonName('example.com'),
    ]);

    expect($subject->get('O'))->toBeEmpty();
});

test('getFirst() returns the first matching component', function () {
    $subject = new CertificateSubject([
        new CommonName('example.com'),
        new Organization('Org A'),
        new Organization('Org B'),
    ]);

    $first = $subject->getFirst('O');

    expect($first)->toBeInstanceOf(Organization::class)
        ->and($first->value)->toBe('Org A');
});

test('getFirst() returns null when no match', function () {
    $subject = new CertificateSubject([
        new CommonName('example.com'),
    ]);

    expect($subject->getFirst('O'))->toBeNull();
});
