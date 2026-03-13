<?php

namespace App\Services;

use App\Config\CaConfiguration;
use App\Config\CertificateTemplateConfig;
use App\Storage\Database;
use App\Storage\Entities\CaMetadata;
use App\Storage\Entities\Certificate;
use App\Storage\Entities\Key;
use App\Storage\Enums\CaFile;
use App\Storage\Enums\CertificateFile;
use App\Storage\Enums\KeyFile;
use Carbon\CarbonImmutable;
use phpseclib3\Crypt\Random;
use phpseclib3\Crypt\RSA;
use phpseclib3\File\X509;
use phpseclib3\Math\BigInteger;
use RuntimeException;

class CertificateSigningService
{
    public function __construct(
        private Database $database,
        private CaConfiguration $config,
    ) {}

    public function issueFromCsr(
        string $templateName,
        string $csrPem,
        RSA\PrivateKey $issuerKey,
        ?string $distinguishedNameOverride = null,
        ?string $serialNumberOverride = null,
    ): Certificate {
        $x509 = new X509;
        $x509->loadCSR($csrPem);

        if (! $x509->validateSignature()) {
            throw new RuntimeException('CSR signature is invalid.');
        }

        $subjectKey = $x509->getPublicKey();
        if (! $subjectKey instanceof RSA\PublicKey) {
            throw new RuntimeException('Only RSA keys are supported.');
        }

        $dn = $distinguishedNameOverride ?? $x509->getDN(X509::DN_STRING);

        $keyId = $this->importPublicKeyFromCsr($subjectKey);

        return $this->sign(
            templateName: $templateName,
            subjectKey: $subjectKey,
            issuerKey: $issuerKey,
            distinguishedName: $dn,
            keyId: $keyId,
            csrPem: $csrPem,
            serialNumberOverride: $serialNumberOverride,
        );
    }

    public function issueFromKeyId(
        string $templateName,
        string $keyId,
        RSA\PrivateKey $issuerKey,
        string $distinguishedName,
        ?string $serialNumberOverride = null,
    ): Certificate {
        $keyEntity = $this->database->keys()->find($keyId);
        if ($keyEntity === null) {
            throw new RuntimeException("Key [{$keyId}] does not exist.");
        }

        $publicKeyPem = $this->database->keys()->getFile($keyId, KeyFile::PublicKey);
        if ($publicKeyPem === null) {
            throw new RuntimeException("Public key file not found for key [{$keyId}].");
        }

        $subjectKey = RSA::loadPublicKey($publicKeyPem);
        if (! $subjectKey instanceof RSA\PublicKey) {
            throw new RuntimeException('Only RSA keys are supported.');
        }

        return $this->sign(
            templateName: $templateName,
            subjectKey: $subjectKey,
            issuerKey: $issuerKey,
            distinguishedName: $distinguishedName,
            keyId: $keyId,
            serialNumberOverride: $serialNumberOverride,
        );
    }

    private function sign(
        string $templateName,
        RSA\PublicKey $subjectKey,
        RSA\PrivateKey $issuerKey,
        string $distinguishedName,
        ?string $keyId = null,
        ?string $csrPem = null,
        ?string $serialNumberOverride = null,
    ): Certificate {
        $template = $this->config->certificateTemplates[$templateName] ?? null;
        if ($template === null) {
            throw new RuntimeException("Template [{$templateName}] not found.");
        }

        $caMetadata = $this->database->ca()->metadata();
        if ($caMetadata?->certificate === null) {
            throw new RuntimeException('CA certificate does not exist. Create or import a CA certificate first.');
        }

        $caCertDetails = $caMetadata->certificate;

        if ($caCertDetails->valid_to->isPast()) {
            throw new RuntimeException('CA certificate has expired.');
        }

        if ($template->ca && $caCertDetails->path_length_constraint !== null && $caCertDetails->path_length_constraint === 0) {
            throw new RuntimeException('CA pathLengthConstraint is 0 — cannot issue subordinate CA certificates.');
        }

        if (! (new X509)->setDN($distinguishedName)) {
            throw new RuntimeException('Invalid distinguished name format.');
        }

        $sequence = $this->getNextSequence($caMetadata);
        $serialNumber = $this->generateSerialNumber($sequence, $serialNumberOverride);

        $issuer = $this->buildIssuerX509($issuerKey);

        $subject = new X509;
        $subject->setDN($distinguishedName);
        $subject->setPublicKey($subjectKey);

        $x509 = new X509;
        $x509->setSerialNumber($serialNumber, 16);

        $this->applyTemplateExtensions($x509, $template, $caCertDetails->path_length_constraint);

        $validFrom = new CarbonImmutable;
        $validTo = new CarbonImmutable($template->validity);

        $x509->setStartDate($validFrom);
        $x509->setEndDate($validTo);

        $result = $x509->sign($issuer, $subject);
        $certPem = $x509->saveX509($result);

        $id = $sequence.'-'.$serialNumber;

        $dnParts = (new X509)->loadX509($certPem);
        $loadedX509 = new X509;
        $loadedX509->loadX509($certPem);
        $commonName = '';
        $dn = $loadedX509->getDN(X509::DN_STRING);
        if (preg_match('/CN=([^,\/]+)/', $dn, $matches)) {
            $commonName = trim($matches[1]);
        }

        $certificate = new Certificate(
            id: $id,
            keyId: $keyId,
            commonName: $commonName,
            type: $template->ca ? 'ca' : 'end-entity',
            serialNumber: $serialNumber,
            notBefore: $validFrom,
            notAfter: $validTo,
            sequence: $sequence,
        );

        $this->database->certificates()->save($certificate);
        $this->database->certificates()->putFile($id, CertificateFile::Certificate, $certPem);

        if ($csrPem !== null) {
            $this->database->certificates()->putFile($id, CertificateFile::Request, $csrPem);
        }

        $this->database->ca()->saveMetadata(new CaMetadata(
            key_id: $caMetadata->key_id,
            certificate: $caMetadata->certificate,
            last_issued_sequence: $sequence,
        ));

        return $certificate;
    }

    private function getNextSequence(CaMetadata $metadata): int
    {
        return ($metadata->last_issued_sequence ?? 0) + 1;
    }

    private function generateSerialNumber(int $sequence, ?string $override = null): string
    {
        if ($override !== null) {
            if (! ctype_xdigit($override)) {
                throw new RuntimeException('Serial number must be a valid hexadecimal string.');
            }

            return $override;
        }

        if ($this->config->certificationAuthority->randomSerialNumbers) {
            return (new BigInteger(Random::string(20) & ("\x7F".str_repeat("\xFF", 19)), 256))->toHex();
        }

        return (new BigInteger($sequence))->toHex();
    }

    private function buildIssuerX509(RSA\PrivateKey $key): X509
    {
        $caCertPem = $this->database->ca()->getFile(CaFile::Certificate);
        if ($caCertPem === null) {
            throw new RuntimeException('CA certificate file not found.');
        }

        $issuer = new X509;
        $issuer->loadX509($caCertPem);
        $issuer->setPrivateKey($key);

        return $issuer;
    }

    private function applyTemplateExtensions(X509 $x509, CertificateTemplateConfig $template, ?int $caPathLengthConstraint): void
    {
        $caConfig = $this->config->certificationAuthority;

        if (count($caConfig->crlDistributionPoints) > 0) {
            $x509->setExtensionValue('id-ce-cRLDistributionPoints', [
                [
                    'distributionPoint' => [
                        'fullName' => array_map(fn ($x) => ['uniformResourceIdentifier' => $x], $caConfig->crlDistributionPoints),
                    ],
                ],
            ]);
        }

        if (count($caConfig->certificateDistributionPoints) > 0) {
            $x509->setExtensionValue('id-pe-authorityInfoAccess', array_map(fn ($x) => [
                'accessMethod' => 'id-ad-caIssuers',
                'accessLocation' => [
                    'uniformResourceIdentifier' => $x,
                ],
            ], $caConfig->certificateDistributionPoints));
        }

        if ($template->ca) {
            $x509->makeCA();
            $subordinateConstraint = null;
            if ($template->pathLengthConstraint !== null) {
                $subordinateConstraint = $template->pathLengthConstraint;
            } elseif ($caPathLengthConstraint !== null && $caPathLengthConstraint > 0) {
                $subordinateConstraint = $caPathLengthConstraint - 1;
            }
            if ($subordinateConstraint !== null) {
                $x509->setExtensionValue('id-ce-basicConstraints', [
                    'cA' => true,
                    'pathLenConstraint' => $subordinateConstraint,
                ], true);
            }
        }
    }

    private function importPublicKeyFromCsr(RSA\PublicKey $key): string
    {
        $fingerprint = $key->getFingerprint();

        $existing = $this->database->keys()->forFingerprint($fingerprint);
        if ($existing !== null) {
            return $existing->id;
        }

        $id = 'csr-'.substr(hash('sha256', $key->toString('PKCS8')), 0, 16);

        $entity = new Key(
            id: $id,
            size: $key->getLength(),
            fingerprint: $fingerprint,
            createdAt: now()->toImmutable(),
            private: false,
        );

        $this->database->keys()->save($entity);
        $this->database->keys()->putFile($id, KeyFile::PublicKey, $key->toString('PKCS8'));

        return $id;
    }
}
