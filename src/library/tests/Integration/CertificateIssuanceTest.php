<?php

declare(strict_types=1);

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Entity\CertificateEntity;
use KDuma\PhpCA\Entity\CsrEntity;
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
use phpseclib3\File\X509;

function createTempCaForIssuanceTests(): CertificationAuthority
{
    $tempDir = sys_get_temp_dir().'/php-ca-test-'.uniqid();
    mkdir($tempDir, 0777, true);
    $filesystem = new Filesystem(new LocalFilesystemAdapter($tempDir));
    $adapter = new FlysystemAdapter($filesystem);
    ExtensionRegistry::registerDefaults();

    return new CertificationAuthority($adapter);
}

/**
 * Helper: sets up a CA with key + self-signed cert + template.
 * Returns [$ca, $caKey, $caCert, $template].
 */
function setupCaInfrastructure(): array
{
    $ca = createTempCaForIssuanceTests();

    // Create CA key
    $caKeyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);
    $caKey = KeyBuilder::fresh($caKeyType)->make();
    $ca->keys->save($caKey);

    // Create self-signed CA certificate
    $caSubject = new CertificateSubject([
        new CommonName('Test Issuing CA'),
        new Organization('Test PKI'),
        new Country('US'),
    ]);

    $caCert = $ca->caCertificates->getBuilder()
        ->selfSigned()
        ->key($caKey)
        ->subject($caSubject)
        ->validity(new DateInterval('P10Y'))
        ->save();

    // Create certificate template
    $template = $ca->templates->getBuilder('end-entity-template')
        ->displayName('End Entity Template')
        ->validity(new DateInterval('P1Y'))
        ->save();

    return [$ca, $caKey, $caCert, $template];
}

test('full certificate issuance flow via CSR', function () {
    [$ca, $caKey, $caCert, $template] = setupCaInfrastructure();

    // Create end-entity key
    $eeKeyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);
    $eeKey = KeyBuilder::fresh($eeKeyType)->make();
    $ca->keys->save($eeKey);

    // Create a CSR for the end-entity
    $csrSubject = new CertificateSubject([
        new CommonName('server.example.com'),
        new Organization('Example Corp'),
    ]);

    // Build the CSR PEM using phpseclib
    $x509 = new X509;
    $x509->setDN($csrSubject->toString());
    $x509->setPrivateKey($eeKey->getPrivateKey());
    $csrResult = $x509->signCSR();
    $csrPem = $x509->saveCSR($csrResult);

    // Manually create a CsrEntity (no CsrBuilder exists)
    $csrEntity = new CsrEntity;
    $csrEntity->subject = $csrSubject;
    $csrEntity->keyId = $eeKey->id;
    $csrEntity->requestedExtensions = [];
    $csrEntity->fingerprint = hash('sha256', $csrPem);
    $csrEntity->csr = $csrPem;
    $ca->csrs->save($csrEntity);

    expect($csrEntity->persisted)->toBeTrue()
        ->and($csrEntity->id)->not->toBeNull()
        ->and($csrEntity->certificateId)->toBeNull();

    // Verify initial sequence
    expect($ca->state->getLastIssuedSequence())->toBe(0);

    // Issue a certificate from the CSR
    $cert = $ca->certificates->getBuilder()
        ->template($template)
        ->signedBy($caCert, $caKey)
        ->fromCsr($csrEntity)
        ->save();

    // Verify certificate entity
    expect($cert)->toBeInstanceOf(CertificateEntity::class)
        ->and($cert->persisted)->toBeTrue()
        ->and($cert->id)->not->toBeNull()
        ->and($cert->version)->toBe(3)
        ->and($cert->serialNumber)->toBeString()->not->toBeEmpty()
        ->and($cert->signatureAlgorithm)->toBe(SignatureAlgorithm::Ed25519)
        ->and($cert->keyId)->toBe($eeKey->id)
        ->and($cert->caCertificateId)->toBe($caCert->id)
        ->and($cert->templateId)->toBe('end-entity-template')
        ->and($cert->fingerprint)->toBeString()->not->toBeEmpty()
        ->and($cert->sequence)->toBe(1);

    // Verify subject comes from CSR
    expect($cert->subject->toString())->toContain('server.example.com')
        ->and($cert->subject->toString())->toContain('Example Corp');

    // Verify issuer comes from CA
    expect($cert->issuer->toString())->toContain('Test Issuing CA');

    // Verify validity
    expect($cert->validity)->toBeInstanceOf(CertificateValidity::class)
        ->and($cert->validity->isValid())->toBeTrue();

    // Verify certificate PEM
    expect($cert->certificate)->toBeString()->toContain('CERTIFICATE');

    // Verify subject and authority key identifiers
    expect($cert->subjectKeyIdentifier)->toBeString()->not->toBeEmpty()
        ->and($cert->authorityKeyIdentifier)->toBeString()->not->toBeEmpty();

    // Verify CSR entity was updated with certificate ID
    $reloadedCsr = $ca->csrs->find($csrEntity->id);
    expect($reloadedCsr->certificateId)->toBe($cert->id);

    // Verify sequence was incremented
    expect($ca->state->getLastIssuedSequence())->toBe(1);
});

test('issuing multiple certificates increments sequence', function () {
    [$ca, $caKey, $caCert, $template] = setupCaInfrastructure();

    $subject = new CertificateSubject([
        new CommonName('host1.example.com'),
    ]);

    // Issue first certificate (directly with key, no CSR)
    $eeKey1 = KeyBuilder::fresh(new EdDSAKeyType(curve: EdDSACurve::Ed25519))->make();
    $ca->keys->save($eeKey1);

    $cert1 = $ca->certificates->getBuilder()
        ->template($template)
        ->signedBy($caCert, $caKey)
        ->key($eeKey1)
        ->subject($subject)
        ->save();

    expect($cert1->sequence)->toBe(1)
        ->and($ca->state->getLastIssuedSequence())->toBe(1);

    // Issue second certificate
    $eeKey2 = KeyBuilder::fresh(new EdDSAKeyType(curve: EdDSACurve::Ed25519))->make();
    $ca->keys->save($eeKey2);

    $subject2 = new CertificateSubject([
        new CommonName('host2.example.com'),
    ]);

    $cert2 = $ca->certificates->getBuilder()
        ->template($template)
        ->signedBy($caCert, $caKey)
        ->key($eeKey2)
        ->subject($subject2)
        ->save();

    expect($cert2->sequence)->toBe(2)
        ->and($ca->state->getLastIssuedSequence())->toBe(2);

    // Verify both can be found
    $allCerts = $ca->certificates->all();
    expect($allCerts)->toHaveCount(2);
});

test('certificate can be found after issuance', function () {
    [$ca, $caKey, $caCert, $template] = setupCaInfrastructure();

    $eeKey = KeyBuilder::fresh(new EdDSAKeyType(curve: EdDSACurve::Ed25519))->make();
    $ca->keys->save($eeKey);

    $subject = new CertificateSubject([
        new CommonName('findable.example.com'),
    ]);

    $cert = $ca->certificates->getBuilder()
        ->template($template)
        ->signedBy($caCert, $caKey)
        ->key($eeKey)
        ->subject($subject)
        ->save();

    $found = $ca->certificates->find($cert->id);

    expect($found->id)->toBe($cert->id)
        ->and($found->serialNumber)->toBe($cert->serialNumber)
        ->and($found->fingerprint)->toBe($cert->fingerprint)
        ->and($found->keyId)->toBe($eeKey->id)
        ->and($found->caCertificateId)->toBe($caCert->id)
        ->and($found->certificate)->toContain('CERTIFICATE');
});
