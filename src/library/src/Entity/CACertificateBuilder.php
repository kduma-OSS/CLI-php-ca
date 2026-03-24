<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use DateInterval;
use DateTimeImmutable;
use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Helpers\FingerprintHelper;
use KDuma\PhpCA\Helpers\KeyHelper;
use KDuma\PhpCA\Helpers\X509ExtensionApplier;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateValidity;
use KDuma\PhpCA\Record\Enum\SignatureAlgorithm;
use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\AuthorityInfoAccessExtension;
use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;
use KDuma\PhpCA\Record\Extension\Extensions\CertificatePoliciesExtension;
use KDuma\PhpCA\Record\Extension\Extensions\CrlDistributionPointsExtension;
use KDuma\PhpCA\Record\Extension\Extensions\ExtKeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Extensions\KeyUsageExtension;
use phpseclib3\File\X509;

class CACertificateBuilder
{
    private ?string $customId = null;

    private bool $selfSigned = false;

    private ?string $importPem = null;

    private string|KeyEntity|null $key = null;

    private ?CertificateSubject $subject = null;

    private ?DateInterval $validity = null;

    /** @var BaseExtension[] */
    private array $extensions = [];

    public function __construct(
        private readonly CertificationAuthority $ca,
    ) {}

    public function id(string $id): static
    {
        $this->customId = $id;

        return $this;
    }

    public function selfSigned(): static
    {
        $this->selfSigned = true;

        return $this;
    }

    public function import(string $pemString): static
    {
        $this->importPem = $pemString;

        return $this;
    }

    public function key(string|KeyEntity $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function subject(CertificateSubject $subject): static
    {
        if ($this->importPem !== null) {
            throw new \LogicException('Cannot set subject when importing — subject is extracted from the certificate.');
        }

        $this->subject = $subject;

        return $this;
    }

    public function validity(DateInterval $validity): static
    {
        if ($this->importPem !== null) {
            throw new \LogicException('Cannot set validity when importing — validity is extracted from the certificate.');
        }

        $this->validity = $validity;

        return $this;
    }

    public function addExtension(BaseExtension $extension): static
    {
        if ($this->importPem !== null) {
            throw new \LogicException('Cannot add extensions when importing — extensions are extracted from the certificate.');
        }

        $this->extensions[] = $extension;

        return $this;
    }

    public function save(): CACertificateEntity
    {
        if ($this->importPem !== null) {
            return $this->saveImported();
        }

        if ($this->selfSigned) {
            return $this->saveSelfSigned();
        }

        throw new \LogicException('Either selfSigned() or import() must be called.');
    }

    private function saveSelfSigned(): CACertificateEntity
    {
        $keyEntity = $this->resolveKey();

        if ($this->subject === null) {
            throw new \LogicException('Subject is required for self-signed certificate.');
        }

        if ($this->validity === null) {
            throw new \LogicException('Validity is required for self-signed certificate.');
        }

        $privateKey = $keyEntity->getPrivateKey();
        if ($privateKey === null) {
            throw new \LogicException('Self-signed certificate requires a private key.');
        }

        $notBefore = new DateTimeImmutable;
        $notAfter = $notBefore->add($this->validity);

        $issuer = new X509;
        $issuer->setDN($this->subject->toString());
        $issuer->setPrivateKey(KeyHelper::prepareForSigning($privateKey));

        $subject = new X509;
        $subject->setDN($this->subject->toString());
        $subject->setPublicKey(KeyHelper::preparePublicKey($keyEntity->getPublicKey()));

        $x509 = new X509;
        $x509->setStartDate($notBefore);
        $x509->setEndDate($notAfter);

        // Apply extensions
        X509ExtensionApplier::apply($x509, $this->extensions);
        // Auto-add SKI
        X509ExtensionApplier::setSubjectKeyIdentifier($x509, $keyEntity->getPublicKey());

        $result = $x509->sign($issuer, $subject);
        $certPem = $x509->saveX509($result);

        return $this->buildEntityFromPem($certPem, $keyEntity);
    }

    private function saveImported(): CACertificateEntity
    {
        $keyEntity = $this->resolveKeyOrLookup($this->importPem);

        return $this->buildEntityFromPem($this->importPem, $keyEntity);
    }

    private function buildEntityFromPem(string $certPem, KeyEntity $keyEntity): CACertificateEntity
    {
        $x509 = new X509;
        $x509->loadX509($certPem);

        $cert = $x509->getCurrentCert();
        $tbs = $cert['tbsCertificate'];

        // Parse version from cert (v1=0, v2=1, v3=2; phpseclib uses 'v1','v2','v3')
        $versionStr = $tbs['version'] ?? 'v1';
        $version = match ($versionStr) {
            'v1' => 1,
            'v2' => 2,
            'v3' => 3,
            default => 3,
        };

        // Detect self-signed: issuer DN == subject DN
        $issuerDn = $x509->getIssuerDN(X509::DN_STRING);
        $subjectDn = $x509->getSubjectDN(X509::DN_STRING);
        $isSelfSigned = $issuerDn === $subjectDn;

        // Parse extensions from the certificate
        $extensions = $this->parseExtensionsFromCert($x509);

        $entity = new CACertificateEntity;
        $entity->id = $this->customId;
        $entity->version = $version;
        $entity->serialNumber = $tbs['serialNumber']->toHex();
        $entity->signatureAlgorithm = SignatureAlgorithm::fromAsn1(
            $cert['signatureAlgorithm']['algorithm']
        );
        $entity->issuer = CertificateSubject::fromString($issuerDn);
        $entity->subject = CertificateSubject::fromString($subjectDn);

        $notBefore = new DateTimeImmutable($tbs['validity']['notBefore']['utcTime'] ?? $tbs['validity']['notBefore']['generalTime']);
        $notAfter = new DateTimeImmutable($tbs['validity']['notAfter']['utcTime'] ?? $tbs['validity']['notAfter']['generalTime']);
        $entity->validity = new CertificateValidity($notBefore, $notAfter);

        $ski = $x509->getExtension('id-ce-subjectKeyIdentifier');
        $entity->subjectKeyIdentifier = is_string($ski) ? bin2hex($ski) : '';

        $aki = $x509->getExtension('id-ce-authorityKeyIdentifier');
        $entity->authorityKeyIdentifier = is_array($aki) && isset($aki['keyIdentifier'])
            ? bin2hex($aki['keyIdentifier'])
            : $entity->subjectKeyIdentifier;

        $entity->extensions = $extensions;
        $entity->keyId = $keyEntity->id;
        $entity->fingerprint = FingerprintHelper::computeCertificateFingerprint($certPem);
        $entity->isSelfSigned = $isSelfSigned;
        $entity->certificate = $certPem;

        $this->ca->caCertificates->save($entity);
        $this->ca->state->setActiveCaCertificateId($entity->id);

        return $entity;
    }

    /**
     * Parse extensions from a loaded X509 certificate into BaseExtension objects.
     *
     * @return BaseExtension[]
     */
    private function parseExtensionsFromCert(X509 $x509): array
    {
        $extensions = [];
        $extNames = $x509->getExtensions();

        foreach ($extNames as $extId) {
            $value = $x509->getExtension($extId);
            $critical = $x509->getExtension($extId, null, 'tbsCertificate/extensions') !== false;

            $ext = match ($extId) {
                'id-ce-basicConstraints' => is_array($value) ? new BasicConstraintsExtension(
                    ca: $value['cA'] ?? false,
                    pathLenConstraint: $value['pathLenConstraint'] ?? null,
                    critical: $critical,
                ) : null,
                'id-ce-keyUsage' => is_array($value) ? new KeyUsageExtension(
                    digitalSignature: in_array('digitalSignature', $value, true),
                    nonRepudiation: in_array('nonRepudiation', $value, true),
                    keyEncipherment: in_array('keyEncipherment', $value, true),
                    dataEncipherment: in_array('dataEncipherment', $value, true),
                    keyAgreement: in_array('keyAgreement', $value, true),
                    keyCertSign: in_array('keyCertSign', $value, true),
                    cRLSign: in_array('cRLSign', $value, true),
                    encipherOnly: in_array('encipherOnly', $value, true),
                    decipherOnly: in_array('decipherOnly', $value, true),
                    critical: $critical,
                ) : null,
                'id-ce-extKeyUsage' => is_array($value) ? new ExtKeyUsageExtension(
                    usages: array_map(fn ($u) => match ($u) {
                        'id-kp-serverAuth' => 'serverAuth',
                        'id-kp-clientAuth' => 'clientAuth',
                        'id-kp-codeSigning' => 'codeSigning',
                        'id-kp-emailProtection' => 'emailProtection',
                        'id-kp-timeStamping' => 'timeStamping',
                        'id-kp-OCSPSigning' => 'OCSPSigning',
                        default => $u,
                    }, $value),
                    critical: $critical,
                ) : null,
                'id-ce-cRLDistributionPoints' => is_array($value) ? new CrlDistributionPointsExtension(
                    uris: $this->extractCrlUris($value),
                    critical: $critical,
                ) : null,
                'id-pe-authorityInfoAccess' => is_array($value) ? new AuthorityInfoAccessExtension(
                    ocspUris: $this->extractAiaUris($value, 'id-ad-ocsp'),
                    caIssuersUris: $this->extractAiaUris($value, 'id-ad-caIssuers'),
                    critical: $critical,
                ) : null,
                'id-ce-certificatePolicies' => is_array($value) ? new CertificatePoliciesExtension(
                    policies: $value,
                    critical: $critical,
                ) : null,
                // SKI and AKI are handled separately (not stored as extensions)
                'id-ce-subjectKeyIdentifier', 'id-ce-authorityKeyIdentifier' => null,
                default => null,
            };

            if ($ext !== null) {
                $extensions[] = $ext;
            }
        }

        return $extensions;
    }

    private function extractCrlUris(array $distributionPoints): array
    {
        $uris = [];
        foreach ($distributionPoints as $dp) {
            if (isset($dp['distributionPoint']['fullName'])) {
                foreach ($dp['distributionPoint']['fullName'] as $name) {
                    if (isset($name['uniformResourceIdentifier'])) {
                        $uris[] = $name['uniformResourceIdentifier'];
                    }
                }
            }
        }

        return $uris;
    }

    private function extractAiaUris(array $accessDescriptions, string $method): array
    {
        $uris = [];
        foreach ($accessDescriptions as $ad) {
            if (($ad['accessMethod'] ?? '') === $method && isset($ad['accessLocation']['uniformResourceIdentifier'])) {
                $uris[] = $ad['accessLocation']['uniformResourceIdentifier'];
            }
        }

        return $uris;
    }

    private function resolveKey(): KeyEntity
    {
        if ($this->key === null) {
            throw new \LogicException('Key is required.');
        }

        if ($this->key instanceof KeyEntity) {
            return $this->key;
        }

        return $this->ca->keys->find($this->key);
    }

    private function resolveKeyOrLookup(string $certPem): KeyEntity
    {
        if ($this->key !== null) {
            return $this->resolveKey();
        }

        $x509 = new X509;
        $x509->loadX509($certPem);
        $publicKey = $x509->getPublicKey();
        $fingerprint = FingerprintHelper::compute($publicKey);

        $keys = $this->ca->keys->all();
        foreach ($keys as $key) {
            if ($key->fingerprint === $fingerprint) {
                return $key;
            }
        }

        throw new \LogicException("No matching key found for certificate public key fingerprint: {$fingerprint}");
    }
}
