<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use DateTimeImmutable;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CrlRecord;
use KDuma\PhpCA\Record\Enum\CrlAttachment;
use KDuma\PhpCA\Record\Enum\SignatureAlgorithm;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

/**
 * @extends BaseEntity<CrlRecord>
 */
class CrlEntity extends BaseEntity
{
    public string $signerKeyId {
        get => $this->signerKeyId;
        set {
            if (isset($this->signerKeyId)) {
                throw new \LogicException('Immutable.');
            } $this->signerKeyId = $value;
        }
    }

    public ?string $signerCertificateId = null {
        get => $this->signerCertificateId;
        set {
            if (isset($this->signerCertificateId)) {
                throw new \LogicException('Immutable.');
            } $this->signerCertificateId = $value;
        }
    }

    public ?string $caCertificateId = null {
        get => $this->caCertificateId;
        set {
            if (isset($this->caCertificateId)) {
                throw new \LogicException('Immutable.');
            } $this->caCertificateId = $value;
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

    public DateTimeImmutable $thisUpdate {
        get => $this->thisUpdate;
        set {
            if (isset($this->thisUpdate)) {
                throw new \LogicException('Immutable.');
            } $this->thisUpdate = $value;
        }
    }

    public ?DateTimeImmutable $nextUpdate = null {
        get => $this->nextUpdate;
        set {
            if (isset($this->nextUpdate)) {
                throw new \LogicException('Immutable.');
            } $this->nextUpdate = $value;
        }
    }

    public int $crlNumber {
        get => $this->crlNumber;
        set {
            if (isset($this->crlNumber)) {
                throw new \LogicException('Immutable.');
            } $this->crlNumber = $value;
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

    private array $_pendingChanges = [];

    public string $crl {
        get {
            if (isset($this->_pendingChanges['crl'])) {
                return $this->_pendingChanges['crl'];
            }

            return $this->attachments->get(CrlAttachment::Crl)->contents();
        }
        set {
            $this->_pendingChanges['crl'] = $value;
        }
    }

    public function isExpired(): bool
    {
        if ($this->nextUpdate === null) {
            return false;
        }

        return $this->nextUpdate < new DateTimeImmutable;
    }

    public function _afterPersisted(): void
    {
        parent::_afterPersisted();

        if (array_key_exists('crl', $this->_pendingChanges)) {
            $this->attachments->put(CrlAttachment::Crl, $this->_pendingChanges['crl']);
        }

        $this->_pendingChanges = [];
    }

    /**
     * @param  CrlEntity  $entity
     * @param  CrlRecord  $record
     */
    protected static function _populateFromRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CrlRecord);
        assert($entity instanceof CrlEntity);

        $entity->signerKeyId = $record->signerKeyId;
        $entity->signerCertificateId = $record->signerCertificateId;
        $entity->caCertificateId = $record->caCertificateId;
        $entity->issuer = $record->issuer;
        $entity->thisUpdate = $record->thisUpdate;
        $entity->nextUpdate = $record->nextUpdate;
        $entity->crlNumber = $record->crlNumber;
        $entity->signatureAlgorithm = $record->signatureAlgorithm;
    }

    /**
     * @param  CrlEntity  $entity
     * @param  CrlRecord  $record
     */
    protected static function _populateToRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CrlRecord);
        assert($entity instanceof CrlEntity);

        $record->signerKeyId = $entity->signerKeyId;
        $record->signerCertificateId = $entity->signerCertificateId;
        $record->caCertificateId = $entity->caCertificateId;
        $record->issuer = $entity->issuer;
        $record->thisUpdate = $entity->thisUpdate;
        $record->nextUpdate = $entity->nextUpdate;
        $record->crlNumber = $entity->crlNumber;
        $record->signatureAlgorithm = $entity->signatureAlgorithm;
    }
}
