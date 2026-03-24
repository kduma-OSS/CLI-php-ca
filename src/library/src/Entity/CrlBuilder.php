<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use DateTimeImmutable;
use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Helpers\KeyHelper;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\Enum\SignatureAlgorithm;
use phpseclib3\File\X509;

class CrlBuilder
{
    private string|CACertificateEntity|null $caCert = null;

    private string|CertificateEntity|null $signerCertificate = null;

    /** @var RevocationEntity[] */
    private array $revocations = [];

    private ?DateTimeImmutable $nextUpdate = null;

    public function __construct(
        private readonly CertificationAuthority $ca,
    ) {}

    public function caCertificate(string|CACertificateEntity $caCert): static
    {
        $this->caCert = $caCert;

        return $this;
    }

    public function signerCertificate(string|CertificateEntity $cert): static
    {
        $this->signerCertificate = $cert;

        return $this;
    }

    /**
     * @param  RevocationEntity[]|RevocationEntity  $revocations
     */
    public function addRevocations(array|RevocationEntity $revocations): static
    {
        if ($revocations instanceof RevocationEntity) {
            $this->revocations[] = $revocations;
        } else {
            $this->revocations = array_merge($this->revocations, $revocations);
        }

        return $this;
    }

    public function nextUpdate(DateTimeImmutable $nextUpdate): static
    {
        $this->nextUpdate = $nextUpdate;

        return $this;
    }

    public function save(): CrlEntity
    {
        $caCertEntity = $this->resolveCaCert();
        $signerCertEntity = $this->resolveSignerCertificate();

        $signerKeyId = $signerCertEntity !== null
            ? $signerCertEntity->keyId
            : $caCertEntity->keyId;

        $signerKeyEntity = $this->ca->keys->find($signerKeyId);

        $privateKey = $signerKeyEntity->getPrivateKey();
        if ($privateKey === null) {
            throw new \LogicException('Signer key must have a private key.');
        }

        $thisUpdate = new DateTimeImmutable;

        // Determine CRL number
        $existingCrls = $this->ca->crls->all();
        $maxCrlNumber = 0;
        foreach ($existingCrls as $crl) {
            if ($crl->crlNumber > $maxCrlNumber) {
                $maxCrlNumber = $crl->crlNumber;
            }
        }
        $crlNumber = $maxCrlNumber + 1;

        // Build CRL using phpseclib
        $issuer = new X509;
        $issuer->loadX509($caCertEntity->certificate);
        $issuer->setPrivateKey(KeyHelper::prepareForSigning($privateKey));

        // Override AKI to point to the signer certificate's SKI for delegated signing
        if ($signerCertEntity !== null) {
            $issuer->setKeyIdentifier(hex2bin($signerCertEntity->subjectKeyIdentifier));
        }

        $crl = new X509;
        $crl->setStartDate($thisUpdate->format('D, d M Y H:i:s O'));
        if ($this->nextUpdate !== null) {
            $crl->setEndDate($this->nextUpdate->format('D, d M Y H:i:s O'));
        }
        $crl->loadCRL($crl->saveCRL($crl->signCRL($issuer, $issuer)));

        // Add revoked certificates
        foreach ($this->revocations as $revocation) {
            $crl->setRevokedCertificateExtension(
                $revocation->serialNumber,
                'id-ce-cRLReasons',
                $revocation->reason->value,
            );
        }

        $signedCrl = $crl->signCRL($issuer, $crl);
        $crlPem = $crl->saveCRL($signedCrl);

        // Parse signed CRL to extract signature algorithm
        $parsedCrl = new X509;
        $parsedCrl->loadCRL($crlPem);
        $crlData = $parsedCrl->getCurrentCert();

        $entity = new CrlEntity;
        $entity->id = (string) $crlNumber;
        $entity->signerKeyId = $signerKeyEntity->id;
        $entity->signerCertificateId = $signerCertEntity?->id;
        $entity->caCertificateId = $caCertEntity->id;
        $entity->issuer = CertificateSubject::fromString($caCertEntity->getSubjectString());
        $entity->thisUpdate = $thisUpdate;
        $entity->nextUpdate = $this->nextUpdate;
        $entity->crlNumber = $crlNumber;
        $entity->signatureAlgorithm = SignatureAlgorithm::fromAsn1(
            $crlData['signatureAlgorithm']['algorithm']
        );
        $entity->crl = $crlPem;

        $this->ca->crls->save($entity);

        return $entity;
    }

    private function resolveCaCert(): CACertificateEntity
    {
        if ($this->caCert === null) {
            throw new \LogicException('CA certificate is required.');
        }

        if ($this->caCert instanceof CACertificateEntity) {
            return $this->caCert;
        }

        return $this->ca->caCertificates->find($this->caCert);
    }

    private function resolveSignerCertificate(): ?CertificateEntity
    {
        if ($this->signerCertificate === null) {
            return null;
        }

        if ($this->signerCertificate instanceof CertificateEntity) {
            return $this->signerCertificate;
        }

        return $this->ca->certificates->find($this->signerCertificate);
    }
}
