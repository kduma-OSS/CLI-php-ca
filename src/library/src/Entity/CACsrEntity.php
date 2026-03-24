<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\Record\CACsrRecord;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\Enum\CACsrAttachment;
use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

/**
 * @extends BaseEntity<CACsrRecord>
 */
class CACsrEntity extends BaseEntity
{
    public CertificateSubject $subject {
        get => $this->subject;
        set {
            if (isset($this->subject)) {
                throw new \LogicException('Immutable.');
            } $this->subject = $value;
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

    public ?string $caCertificateId = null; // mutable — updated when CA cert is imported

    /** @var BaseExtension[] */
    public array $requestedExtensions = [] {
        get => $this->requestedExtensions;
        set {
            if ($this->requestedExtensions !== []) {
                throw new \LogicException('Immutable.');
            } $this->requestedExtensions = $value;
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

    private array $_pendingChanges = [];

    public string $csr {
        get {
            if (isset($this->_pendingChanges['csr'])) {
                return $this->_pendingChanges['csr'];
            }

            return $this->attachments->get(CACsrAttachment::Csr)->contents();
        }
        set {
            $this->_pendingChanges['csr'] = $value;
        }
    }

    public function getSubjectString(): string
    {
        return $this->subject->toString();
    }

    public function _afterPersisted(): void
    {
        parent::_afterPersisted();

        if (array_key_exists('csr', $this->_pendingChanges)) {
            $this->attachments->put(CACsrAttachment::Csr, $this->_pendingChanges['csr']);
        }

        $this->_pendingChanges = [];
    }

    protected static function _populateFromRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CACsrRecord);
        assert($entity instanceof CACsrEntity);

        $entity->subject = $record->subject;
        $entity->keyId = $record->keyId;
        $entity->caCertificateId = $record->caCertificateId;
        $entity->requestedExtensions = $record->requestedExtensions;
        $entity->fingerprint = $record->fingerprint;
    }

    protected static function _populateToRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CACsrRecord);
        assert($entity instanceof CACsrEntity);

        $record->subject = $entity->subject;
        $record->keyId = $entity->keyId;
        $record->caCertificateId = $entity->caCertificateId;
        $record->requestedExtensions = $entity->requestedExtensions;
        $record->fingerprint = $entity->fingerprint;
    }
}
