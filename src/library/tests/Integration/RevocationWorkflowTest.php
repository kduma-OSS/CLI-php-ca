<?php

declare(strict_types=1);

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Entity\CrlEntity;
use KDuma\PhpCA\Entity\CsrEntity;
use KDuma\PhpCA\Entity\KeyBuilder;
use KDuma\PhpCA\Entity\RevocationEntity;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateSubject\DN\CommonName;
use KDuma\PhpCA\Record\CertificateSubject\DN\Country;
use KDuma\PhpCA\Record\CertificateSubject\DN\Organization;
use KDuma\PhpCA\Record\Enum\RevocationReason;
use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;
use KDuma\SimpleDAL\Adapter\Flysystem\FlysystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use phpseclib3\File\X509;

function createTempCaForRevocationTests(): CertificationAuthority
{
    $tempDir = sys_get_temp_dir().'/php-ca-test-'.uniqid();
    mkdir($tempDir, 0777, true);
    $filesystem = new Filesystem(new LocalFilesystemAdapter($tempDir));
    $adapter = new FlysystemAdapter($filesystem);
    ExtensionRegistry::registerDefaults();

    return new CertificationAuthority($adapter);
}

/**
 * Helper: sets up a complete CA + issues a certificate, returning all entities.
 * Returns [$ca, $caKey, $caCert, $cert].
 */
function setupCaWithIssuedCert(): array
{
    $ca = createTempCaForRevocationTests();

    // Create CA key
    $caKey = KeyBuilder::fresh(new EdDSAKeyType(curve: EdDSACurve::Ed25519))->make();
    $ca->keys->save($caKey);

    // Create self-signed CA certificate
    $caSubject = new CertificateSubject([
        new CommonName('Revocation Test CA'),
        new Organization('Test PKI'),
        new Country('US'),
    ]);

    $caCert = $ca->caCertificates->getBuilder()
        ->selfSigned()
        ->key($caKey)
        ->subject($caSubject)
        ->validity(new DateInterval('P10Y'))
        ->save();

    // Create template
    $template = $ca->templates->getBuilder('revocation-test-template')
        ->displayName('Revocation Test Template')
        ->validity(new DateInterval('P1Y'))
        ->save();

    // Create end-entity key
    $eeKey = KeyBuilder::fresh(new EdDSAKeyType(curve: EdDSACurve::Ed25519))->make();
    $ca->keys->save($eeKey);

    // Create CSR
    $csrSubject = new CertificateSubject([
        new CommonName('revoked.example.com'),
        new Organization('Example Corp'),
    ]);

    $x509 = new X509;
    $x509->setDN($csrSubject->toString());
    $x509->setPrivateKey($eeKey->getPrivateKey());
    $csrResult = $x509->signCSR();
    $csrPem = $x509->saveCSR($csrResult);

    $csrEntity = new CsrEntity;
    $csrEntity->subject = $csrSubject;
    $csrEntity->keyId = $eeKey->id;
    $csrEntity->requestedExtensions = [];
    $csrEntity->fingerprint = hash('sha256', $csrPem);
    $csrEntity->csr = $csrPem;
    $ca->csrs->save($csrEntity);

    // Issue certificate
    $cert = $ca->certificates->getBuilder()
        ->template($template)
        ->signedBy($caCert, $caKey)
        ->fromCsr($csrEntity)
        ->save();

    return [$ca, $caKey, $caCert, $cert];
}

test('revoke a certificate and verify revocation entity', function () {
    [$ca, $caKey, $caCert, $cert] = setupCaWithIssuedCert();

    // Create a revocation entity
    $revocation = new RevocationEntity;
    $revocation->certificateId = $cert->id;
    $revocation->serialNumber = $cert->serialNumber;
    $revocation->revokedAt = new DateTimeImmutable;
    $revocation->reason = RevocationReason::KeyCompromise;
    $revocation->caCertificateId = $caCert->id;

    $ca->revocations->save($revocation);

    expect($revocation->persisted)->toBeTrue()
        ->and($revocation->id)->not->toBeNull();

    // Verify revocation entity properties
    $found = $ca->revocations->find($revocation->id);

    expect($found)->toBeInstanceOf(RevocationEntity::class)
        ->and($found->certificateId)->toBe($cert->id)
        ->and($found->serialNumber)->toBe($cert->serialNumber)
        ->and($found->reason)->toBe(RevocationReason::KeyCompromise)
        ->and($found->caCertificateId)->toBe($caCert->id)
        ->and($found->revokedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

test('find revocations for a specific certificate', function () {
    [$ca, $caKey, $caCert, $cert] = setupCaWithIssuedCert();

    $revocation = new RevocationEntity;
    $revocation->certificateId = $cert->id;
    $revocation->serialNumber = $cert->serialNumber;
    $revocation->revokedAt = new DateTimeImmutable;
    $revocation->reason = RevocationReason::Superseded;
    $revocation->caCertificateId = $caCert->id;

    $ca->revocations->save($revocation);

    $found = $ca->revocations->forCertificate($cert->id);

    expect($found)->toHaveCount(1)
        ->and($found[0]->certificateId)->toBe($cert->id)
        ->and($found[0]->reason)->toBe(RevocationReason::Superseded);
});

test('generate CRL after revoking a certificate', function () {
    [$ca, $caKey, $caCert, $cert] = setupCaWithIssuedCert();

    // Revoke the certificate
    $revocation = new RevocationEntity;
    $revocation->certificateId = $cert->id;
    $revocation->serialNumber = $cert->serialNumber;
    $revocation->revokedAt = new DateTimeImmutable;
    $revocation->reason = RevocationReason::KeyCompromise;
    $revocation->caCertificateId = $caCert->id;

    $ca->revocations->save($revocation);

    // Generate CRL
    $nextUpdate = new DateTimeImmutable('+30 days');

    $crl = $ca->crls->getBuilder()
        ->caCertificate($caCert)

        ->addRevocations($revocation)
        ->nextUpdate($nextUpdate)
        ->save();

    // Verify CRL entity
    expect($crl)->toBeInstanceOf(CrlEntity::class)
        ->and($crl->persisted)->toBeTrue()
        ->and($crl->id)->not->toBeNull()
        ->and($crl->signerKeyId)->toBe($caKey->id)
        ->and($crl->caCertificateId)->toBe($caCert->id)
        ->and($crl->crlNumber)->toBe(1)
        ->and($crl->thisUpdate)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($crl->nextUpdate)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($crl->issuer->toString())->toContain('Revocation Test CA')
        ->and($crl->isExpired())->toBeFalse();

    // Verify CRL PEM
    expect($crl->crl)->toBeString()->toContain('X509 CRL');

    // Verify CRL can be found
    $foundCrl = $ca->crls->find($crl->id);

    expect($foundCrl->id)->toBe($crl->id)
        ->and($foundCrl->crlNumber)->toBe(1)
        ->and($foundCrl->signerKeyId)->toBe($caKey->id)
        ->and($foundCrl->caCertificateId)->toBe($caCert->id)
        ->and($foundCrl->crl)->toContain('X509 CRL');
});

test('CRL number increments with successive CRL generations', function () {
    [$ca, $caKey, $caCert, $cert] = setupCaWithIssuedCert();

    // First CRL (empty, no revocations)
    $crl1 = $ca->crls->getBuilder()
        ->caCertificate($caCert)

        ->save();

    expect($crl1->crlNumber)->toBe(1);

    // Second CRL
    $crl2 = $ca->crls->getBuilder()
        ->caCertificate($caCert)

        ->save();

    expect($crl2->crlNumber)->toBe(2);

    // Verify both exist
    $allCrls = $ca->crls->all();
    expect($allCrls)->toHaveCount(2);
});
