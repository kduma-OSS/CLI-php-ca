<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\CertificateSubject\BaseDN;
use KDuma\PhpCA\Record\CertificateSubject\DN\CommonName;
use KDuma\PhpCA\Record\CertificateSubject\DN\Organization;
use KDuma\PhpCA\Record\CertificateSubject\DN\OrganizationalUnit;
use KDuma\PhpCA\Record\CertificateSubject\DN\Country;
use KDuma\PhpCA\Record\CertificateSubject\DN\State;
use KDuma\PhpCA\Record\CertificateSubject\DN\Locality;
use KDuma\PhpCA\Record\CertificateSubject\DN\EmailAddress;
use KDuma\PhpCA\Record\CertificateSubject\DN\SerialNumber;
use KDuma\PhpCA\Record\CertificateSubject\DN\Title;
use KDuma\PhpCA\Record\CertificateSubject\DN\Description;
use KDuma\PhpCA\Record\CertificateSubject\DN\PostalAddress;
use KDuma\PhpCA\Record\CertificateSubject\DN\PostalCode;
use KDuma\PhpCA\Record\CertificateSubject\DN\StreetAddress;
use KDuma\PhpCA\Record\CertificateSubject\DN\Name;
use KDuma\PhpCA\Record\CertificateSubject\DN\GivenName;
use KDuma\PhpCA\Record\CertificateSubject\DN\Surname;
use KDuma\PhpCA\Record\CertificateSubject\DN\Initials;
use KDuma\PhpCA\Record\CertificateSubject\DN\GenerationQualifier;
use KDuma\PhpCA\Record\CertificateSubject\DN\DnQualifier;
use KDuma\PhpCA\Record\CertificateSubject\DN\Pseudonym;
use KDuma\PhpCA\Record\CertificateSubject\DN\UniqueIdentifier;
use KDuma\PhpCA\Record\CertificateSubject\DN\Role;
use KDuma\PhpCA\Record\CertificateSubject\DN\BusinessCategory;
use KDuma\PhpCA\Record\CertificateSubject\DN\JurisdictionCountry;
use KDuma\PhpCA\Record\CertificateSubject\DN\JurisdictionState;
use KDuma\PhpCA\Record\CertificateSubject\DN\JurisdictionLocality;

dataset('dn_types', [
    'CommonName'           => [CommonName::class,           'CN',                  '2.5.4.3',                    'Test Common Name'],
    'Organization'         => [Organization::class,         'O',                   '2.5.4.10',                   'Test Organization'],
    'OrganizationalUnit'   => [OrganizationalUnit::class,   'OU',                  '2.5.4.11',                   'Test OU'],
    'Country'              => [Country::class,              'C',                   '2.5.4.6',                    'US'],
    'State'                => [State::class,                'ST',                  '2.5.4.8',                    'California'],
    'Locality'             => [Locality::class,             'L',                   '2.5.4.7',                    'San Francisco'],
    'EmailAddress'         => [EmailAddress::class,         'emailAddress',        '1.2.840.113549.1.9.1',       'test@example.com'],
    'SerialNumber'         => [SerialNumber::class,         'serialNumber',        '2.5.4.5',                    '123456'],
    'Title'                => [Title::class,                'title',               '2.5.4.12',                   'CEO'],
    'Description'          => [Description::class,          'description',         '2.5.4.13',                   'A test description'],
    'PostalAddress'        => [PostalAddress::class,        'postalAddress',       '2.5.4.16',                   '123 Main St'],
    'PostalCode'           => [PostalCode::class,           'postalCode',          '2.5.4.17',                   '12345'],
    'StreetAddress'        => [StreetAddress::class,        'streetAddress',       '2.5.4.9',                    '456 Oak Ave'],
    'Name'                 => [Name::class,                 'name',                '2.5.4.41',                   'John Doe'],
    'GivenName'            => [GivenName::class,            'givenName',           '2.5.4.42',                   'John'],
    'Surname'              => [Surname::class,              'SN',                  '2.5.4.4',                    'Doe'],
    'Initials'             => [Initials::class,             'initials',            '2.5.4.43',                   'JD'],
    'GenerationQualifier'  => [GenerationQualifier::class,  'generationQualifier', '2.5.4.44',                   'Jr.'],
    'DnQualifier'          => [DnQualifier::class,          'dnQualifier',         '2.5.4.46',                   'qualifier-value'],
    'Pseudonym'            => [Pseudonym::class,            'pseudonym',           '2.5.4.65',                   'JDoe'],
    'UniqueIdentifier'     => [UniqueIdentifier::class,     'uniqueIdentifier',    '2.5.4.45',                   'unique-id-123'],
    'Role'                 => [Role::class,                 'role',                '2.5.4.72',                   'admin'],
    'BusinessCategory'     => [BusinessCategory::class,     'businessCategory',    '2.5.4.15',                   'Technology'],
    'JurisdictionCountry'  => [JurisdictionCountry::class,  'jurisdictionC',       '1.3.6.1.4.1.311.60.2.1.3',   'US'],
    'JurisdictionState'    => [JurisdictionState::class,    'jurisdictionST',      '1.3.6.1.4.1.311.60.2.1.2',   'Delaware'],
    'JurisdictionLocality' => [JurisdictionLocality::class, 'jurisdictionL',       '1.3.6.1.4.1.311.60.2.1.1',   'Wilmington'],
]);

test('DN type can be instantiated and holds value', function (string $class, string $shortName, string $oid, string $testValue) {
    $dn = new $class($testValue);

    expect($dn)->toBeInstanceOf(BaseDN::class)
        ->and($dn->value)->toBe($testValue);
})->with('dn_types');

test('DN type returns correct shortName', function (string $class, string $shortName, string $oid, string $testValue) {
    expect($class::shortName())->toBe($shortName);
})->with('dn_types');

test('DN type returns correct OID', function (string $class, string $shortName, string $oid, string $testValue) {
    expect($class::oid())->toBe($oid);
})->with('dn_types');

test('DN type toArray returns correct structure', function (string $class, string $shortName, string $oid, string $testValue) {
    $dn = new $class($testValue);
    $array = $dn->toArray();

    expect($array)->toBe([
        'type' => $shortName,
        'value' => $testValue,
    ]);
})->with('dn_types');

test('DN type is readonly', function (string $class, string $shortName, string $oid, string $testValue) {
    $dn = new $class($testValue);

    $reflection = new ReflectionClass($dn);
    expect($reflection->isReadOnly())->toBeTrue();
})->with('dn_types');

test('all 26 DN types are covered', function () {
    $dnClasses = [
        CommonName::class, Organization::class, OrganizationalUnit::class,
        Country::class, State::class, Locality::class, EmailAddress::class,
        SerialNumber::class, Title::class, Description::class,
        PostalAddress::class, PostalCode::class, StreetAddress::class,
        Name::class, GivenName::class, Surname::class, Initials::class,
        GenerationQualifier::class, DnQualifier::class, Pseudonym::class,
        UniqueIdentifier::class, Role::class, BusinessCategory::class,
        JurisdictionCountry::class, JurisdictionState::class, JurisdictionLocality::class,
    ];

    expect($dnClasses)->toHaveCount(26);

    // Verify all have unique shortNames
    $shortNames = array_map(fn ($class) => $class::shortName(), $dnClasses);
    expect(array_unique($shortNames))->toHaveCount(26);

    // Verify all have unique OIDs
    $oids = array_map(fn ($class) => $class::oid(), $dnClasses);
    expect(array_unique($oids))->toHaveCount(26);
});
