<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\Record\CertificateRecord;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateValidity;
use KDuma\PhpCA\Record\Enum\CertificateAttachment;
use KDuma\PhpCA\Record\Enum\SignatureAlgorithm;
use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

/**
 * @extends BaseEntity<CertificateRecord>
 */
class CertificateEntity extends BaseEntity
{
    public int $version {
        get => $this->version;
        set {
            if (isset($this->version)) {
                throw new \LogicException('Cannot modify version on an existing entity.');
            }
            $this->version = $value;
        }
    }

    public string $serialNumber {
        get => $this->serialNumber;
        set {
            if (isset($this->serialNumber)) {
                throw new \LogicException('Cannot modify serialNumber on an existing entity.');
            }
            $this->serialNumber = $value;
        }
    }

    public SignatureAlgorithm $signatureAlgorithm {
        get => $this->signatureAlgorithm;
        set {
            if (isset($this->signatureAlgorithm)) {
                throw new \LogicException('Cannot modify signatureAlgorithm on an existing entity.');
            }
            $this->signatureAlgorithm = $value;
        }
    }

    public CertificateSubject $issuer {
        get => $this->issuer;
        set {
            if (isset($this->issuer)) {
                throw new \LogicException('Cannot modify issuer on an existing entity.');
            }
            $this->issuer = $value;
        }
    }

    public CertificateSubject $subject {
        get => $this->subject;
        set {
            if (isset($this->subject)) {
                throw new \LogicException('Cannot modify subject on an existing entity.');
            }
            $this->subject = $value;
        }
    }

    public CertificateValidity $validity {
        get => $this->validity;
        set {
            if (isset($this->validity)) {
                throw new \LogicException('Cannot modify validity on an existing entity.');
            }
            $this->validity = $value;
        }
    }

    public string $subjectKeyIdentifier {
        get => $this->subjectKeyIdentifier;
        set {
            if (isset($this->subjectKeyIdentifier)) {
                throw new \LogicException('Cannot modify subjectKeyIdentifier on an existing entity.');
            }
            $this->subjectKeyIdentifier = $value;
        }
    }

    public string $authorityKeyIdentifier {
        get => $this->authorityKeyIdentifier;
        set {
            if (isset($this->authorityKeyIdentifier)) {
                throw new \LogicException('Cannot modify authorityKeyIdentifier on an existing entity.');
            }
            $this->authorityKeyIdentifier = $value;
        }
    }

    /** @var BaseExtension[] */
    public array $extensions = [] {
        get => $this->extensions;
        set {
            if ($this->extensions !== []) {
                throw new \LogicException('Cannot modify extensions on an existing entity.');
            }
            $this->extensions = $value;
        }
    }

    public string $keyId {
        get => $this->keyId;
        set {
            if (isset($this->keyId)) {
                throw new \LogicException('Cannot modify keyId on an existing entity.');
            }
            $this->keyId = $value;
        }
    }

    public string $caCertificateId {
        get => $this->caCertificateId;
        set {
            if (isset($this->caCertificateId)) {
                throw new \LogicException('Cannot modify caCertificateId on an existing entity.');
            }
            $this->caCertificateId = $value;
        }
    }

    public int $sequence {
        get => $this->sequence;
        set {
            if (isset($this->sequence)) {
                throw new \LogicException('Cannot modify sequence on an existing entity.');
            }
            $this->sequence = $value;
        }
    }

    public string $fingerprint {
        get => $this->fingerprint;
        set {
            if (isset($this->fingerprint)) {
                throw new \LogicException('Cannot modify fingerprint on an existing entity.');
            }
            $this->fingerprint = $value;
        }
    }

    public ?string $templateId = null {
        get => $this->templateId;
        set {
            if (isset($this->templateId)) {
                throw new \LogicException('Cannot modify templateId on an existing entity.');
            }
            $this->templateId = $value;
        }
    }

    private array $_pendingChanges = [];

    public string $certificate {
        get {
            if (isset($this->_pendingChanges['certificate'])) {
                return $this->_pendingChanges['certificate'];
            }
            return $this->attachments->get(CertificateAttachment::Certificate)->contents();
        }
        set {
            $this->_pendingChanges['certificate'] = $value;
        }
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
            $this->attachments->put(CertificateAttachment::Certificate, $this->_pendingChanges['certificate']);
        }

        $this->_pendingChanges = [];
    }

    /**
     * @param CertificateEntity $entity
     * @param CertificateRecord $record
     */
    protected static function _populateFromRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CertificateRecord);
        assert($entity instanceof CertificateEntity);

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
        $entity->caCertificateId = $record->caCertificateId;
        $entity->sequence = $record->sequence;
        $entity->fingerprint = $record->fingerprint;
        $entity->templateId = $record->templateId;
    }

    /**
     * @param CertificateEntity $entity
     * @param CertificateRecord $record
     */
    protected static function _populateToRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CertificateRecord);
        assert($entity instanceof CertificateEntity);

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
        $record->caCertificateId = $entity->caCertificateId;
        $record->sequence = $entity->sequence;
        $record->fingerprint = $entity->fingerprint;
        $record->templateId = $entity->templateId;
    }
}
