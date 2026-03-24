<?php

declare(strict_types=1);

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Entity\CACertificateEntity;
use KDuma\PhpCA\Entity\KeyBuilder;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateSubject\DN\CommonName;
use KDuma\PhpCA\Record\CertificateSubject\DN\Country;
use KDuma\PhpCA\Record\CertificateSubject\DN\Organization;
use KDuma\PhpCA\Record\CertificateValidity;
use KDuma\PhpCA\Record\Enum\SignatureAlgorithm;
use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;
use KDuma\SimpleDAL\Adapter\Flysystem\FlysystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

function createTempCaForCACertTests(): CertificationAuthority
{
    $tempDir = sys_get_temp_dir().'/php-ca-test-'.uniqid();
    mkdir($tempDir, 0777, true);
    $filesystem = new Filesystem(new LocalFilesystemAdapter($tempDir));
    $adapter = new FlysystemAdapter($filesystem);
    ExtensionRegistry::registerDefaults();

    return new CertificationAuthority($adapter);
}

test('generate CA key and create self-signed CA certificate', function () {
    $ca = createTempCaForCACertTests();

    // Generate and save the CA key
    $keyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);
    $keyEntity = KeyBuilder::fresh($keyType)->make();
    $ca->keys->save($keyEntity);

    // Create self-signed CA certificate via the builder
    $subject = new CertificateSubject([
        new CommonName('Test Root CA'),
        new Organization('Test Organization'),
        new Country('US'),
    ]);

    $caCert = $ca->caCertificates->getBuilder()
        ->selfSigned()
        ->key($keyEntity)
        ->subject($subject)
        ->validity(new DateInterval('P10Y'))
        ->save();

    // Verify entity type and persistence
    expect($caCert)->toBeInstanceOf(CACertificateEntity::class)
        ->and($caCert->persisted)->toBeTrue()
        ->and($caCert->id)->not->toBeNull();

    // Verify certificate properties
    expect($caCert->version)->toBe(3)
        ->and($caCert->serialNumber)->toBeString()->not->toBeEmpty()
        ->and($caCert->signatureAlgorithm)->toBe(SignatureAlgorithm::Ed25519)
        ->and($caCert->isSelfSigned)->toBeTrue()
        ->and($caCert->keyId)->toBe($keyEntity->id)
        ->and($caCert->fingerprint)->toBeString()->not->toBeEmpty();

    // Verify subject and issuer match (self-signed)
    expect($caCert->subject->toString())->toContain('Test Root CA')
        ->and($caCert->issuer->toString())->toContain('Test Root CA')
        ->and($caCert->getSubjectString())->toBe($caCert->getIssuerString());

    // Verify validity
    expect($caCert->validity)->toBeInstanceOf(CertificateValidity::class)
        ->and($caCert->validity->isValid())->toBeTrue()
        ->and($caCert->validity->isExpired())->toBeFalse()
        ->and($caCert->isExpired())->toBeFalse();

    // Verify subject and authority key identifiers
    expect($caCert->subjectKeyIdentifier)->toBeString()->not->toBeEmpty()
        ->and($caCert->authorityKeyIdentifier)->toBeString()->not->toBeEmpty();

    // Verify certificate PEM
    expect($caCert->certificate)->toBeString()->toContain('CERTIFICATE');

    // Verify CA state was updated
    expect($ca->state->getActiveCaCertificateId())->toBe($caCert->id);
});

test('find CA certificate after creation', function () {
    $ca = createTempCaForCACertTests();

    $keyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);
    $keyEntity = KeyBuilder::fresh($keyType)->make();
    $ca->keys->save($keyEntity);

    $subject = new CertificateSubject([
        new CommonName('Test Root CA'),
        new Organization('Test Org'),
    ]);

    $caCert = $ca->caCertificates->getBuilder()
        ->selfSigned()
        ->key($keyEntity)
        ->subject($subject)
        ->validity(new DateInterval('P5Y'))
        ->save();

    // Find the CA certificate
    $found = $ca->caCertificates->find($caCert->id);

    expect($found)->toBeInstanceOf(CACertificateEntity::class)
        ->and($found->id)->toBe($caCert->id)
        ->and($found->serialNumber)->toBe($caCert->serialNumber)
        ->and($found->fingerprint)->toBe($caCert->fingerprint)
        ->and($found->isSelfSigned)->toBeTrue()
        ->and($found->keyId)->toBe($keyEntity->id)
        ->and($found->certificate)->toContain('CERTIFICATE');
});

test('create CA certificate with custom ID', function () {
    $ca = createTempCaForCACertTests();

    $keyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);
    $keyEntity = KeyBuilder::fresh($keyType)->make();
    $ca->keys->save($keyEntity);

    $subject = new CertificateSubject([
        new CommonName('Custom ID CA'),
    ]);

    $caCert = $ca->caCertificates->getBuilder()
        ->id('my-custom-ca-id')
        ->selfSigned()
        ->key($keyEntity)
        ->subject($subject)
        ->validity(new DateInterval('P1Y'))
        ->save();

    expect($caCert->id)->toBe('my-custom-ca-id');

    $found = $ca->caCertificates->find('my-custom-ca-id');
    expect($found->id)->toBe('my-custom-ca-id');
});
