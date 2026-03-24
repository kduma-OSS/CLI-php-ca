<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\Record\CACertificateRecord;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateValidity;
use KDuma\PhpCA\Record\Enum\CACertificateAttachment;
use KDuma\PhpCA\Record\Enum\SignatureAlgorithm;
use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

/**
 * @extends BaseEntity<CACertificateRecord>
 */
class CACertificateEntity extends BaseEntity
{
    public int $version {
        get => $this->version;
        set {
            if (isset($this->version)) {
                throw new \LogicException('Immutable.');
            } $this->version = $value;
        }
    }

    public string $serialNumber {
        get => $this->serialNumber;
        set {
            if (isset($this->serialNumber)) {
                throw new \LogicException('Immutable.');
            } $this->serialNumber = $value;
        }
    }

    public SignatureAlgorithm $signatureAlgorithm {
        get => $this->signatureAlgorithm;
        set {
            if (isset($this->signatureAlgorithm)) {
                throw new \LogicException('Immutable.');
            } $this->signatureAlgorithm = $value;
        }
    }

    public CertificateSubject $issuer {
        get => $this->issuer;
        set {
            if (isset($this->issuer)) {
                throw new \LogicException('Immutable.');
            } $this->issuer = $value;
        }
    }

    public CertificateSubject $subject {
        get => $this->subject;
        set {
            if (isset($this->subject)) {
                throw new \LogicException('Immutable.');
            } $this->subject = $value;
        }
    }

    public CertificateValidity $validity {
        get => $this->validity;
        set {
            if (isset($this->validity)) {
                throw new \LogicException('Immutable.');
            } $this->validity = $value;
        }
    }

    public string $subjectKeyIdentifier {
        get => $this->subjectKeyIdentifier;
        set {
            if (isset($this->subjectKeyIdentifier)) {
                throw new \LogicException('Immutable.');
            } $this->subjectKeyIdentifier = $value;
        }
    }

    public string $authorityKeyIdentifier {
        get => $this->authorityKeyIdentifier;
        set {
            if (isset($this->authorityKeyIdentifier)) {
                throw new \LogicException('Immutable.');
            } $this->authorityKeyIdentifier = $value;
        }
    }

    /** @var BaseExtension[] */
    public array $extensions = [] {
        get => $this->extensions;
        set {
            if ($this->extensions !== []) {
                throw new \LogicException('Immutable.');
            } $this->extensions = $value;
        }
    }

    public string $keyId {
        get => $this->keyId;
        set {
            if (isset($this->keyId)) {
                throw new \LogicException('Immutable.');
            } $this->keyId = $value;
        }
    }

    public string $fingerprint {
        get => $this->fingerprint;
        set {
            if (isset($this->fingerprint)) {
                throw new \LogicException('Immutable.');
            } $this->fingerprint = $value;
        }
    }

    public bool $isSelfSigned {
        get => $this->isSelfSigned;
        set {
            if (isset($this->isSelfSigned)) {
                throw new \LogicException('Immutable.');
            } $this->isSelfSigned = $value;
        }
    }

    private array $_pendingChanges = [];

    public string $certificate {
        get {
            if (isset($this->_pendingChanges['certificate'])) {
                return $this->_pendingChanges['certificate'];
            }

            return $this->attachments->get(CACertificateAttachment::Certificate)->contents();
        }
        set {
            $this->_pendingChanges['certificate'] = $value;
        }
    }

    public ?string $chain {
        get {
            if (array_key_exists('chain', $this->_pendingChanges)) {
                return $this->_pendingChanges['chain'];
            }

            return $this->attachments->getOrNull(CACertificateAttachment::Chain)?->contents();
        }
        set {
            $this->_pendingChanges['chain'] = $value;
        }
    }

    public function isExpired(): bool
    {
        return $this->validity->isExpired();
    }

    public function canIssueSubordinateCAs(): bool
    {
        foreach ($this->extensions as $ext) {
            if ($ext instanceof BasicConstraintsExtension) {
                return $ext->ca && ($ext->pathLenConstraint === null || $ext->pathLenConstraint > 0);
            }
        }

        return false;
    }

    public function getSubjectString(): string
    {
        return $this->subject->toString();
    }

    public function getIssuerString(): string
    {
        return $this->issuer->toString();
    }

    public function _afterPersisted(): void
    {
        parent::_afterPersisted();

        if (array_key_exists('certificate', $this->_pendingChanges)) {
            $this->attachments->put(CACertificateAttachment::Certificate, $this->_pendingChanges['certificate']);
        }

        if (array_key_exists('chain', $this->_pendingChanges)) {
            $this->attachments->put(CACertificateAttachment::Chain, $this->_pendingChanges['chain']);
        }

        $this->_pendingChanges = [];
    }

    protected static function _populateFromRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CACertificateRecord);
        assert($entity instanceof CACertificateEntity);

        $entity->version = $record->version;
        $entity->serialNumber = $record->serialNumber;
        $entity->signatureAlgorithm = $record->signatureAlgorithm;
        $entity->issuer = $record->issuer;
        $entity->subject = $record->subject;
        $entity->validity = $record->validity;
        $entity->subjectKeyIdentifier = $record->subjectKeyIdentifier;
        $entity->authorityKeyIdentifier = $record->authorityKeyIdentifier;
        $entity->extensions = $record->extensions;
        $entity->keyId = $record->keyId;
        $entity->fingerprint = $record->fingerprint;
        $entity->isSelfSigned = $record->isSelfSigned;
    }

    protected static function _populateToRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CACertificateRecord);
        assert($entity instanceof CACertificateEntity);

        $record->version = $entity->version;
        $record->serialNumber = $entity->serialNumber;
        $record->signatureAlgorithm = $entity->signatureAlgorithm;
        $record->issuer = $entity->issuer;
        $record->subject = $entity->subject;
        $record->validity = $entity->validity;
        $record->subjectKeyIdentifier = $entity->subjectKeyIdentifier;
        $record->authorityKeyIdentifier = $entity->authorityKeyIdentifier;
        $record->extensions = $entity->extensions;
        $record->keyId = $entity->keyId;
        $record->fingerprint = $entity->fingerprint;
        $record->isSelfSigned = $entity->isSelfSigned;
    }
}
