<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use DateTimeImmutable;
use KDuma\PhpCA\Record\Enum\RevocationReason;
use KDuma\PhpCA\Record\RevocationRecord;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

/**
 * @extends BaseEntity<RevocationRecord>
 */
class RevocationEntity extends BaseEntity
{
    public string $certificateId {
        get => $this->certificateId;
        set {
            if (isset($this->certificateId)) {
                throw new \LogicException('Immutable.');
            } $this->certificateId = $value;
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

    public DateTimeImmutable $revokedAt {
        get => $this->revokedAt;
        set {
            if (isset($this->revokedAt)) {
                throw new \LogicException('Immutable.');
            } $this->revokedAt = $value;
        }
    }

    public RevocationReason $reason {
        get => $this->reason;
        set {
            if (isset($this->reason)) {
                throw new \LogicException('Immutable.');
            } $this->reason = $value;
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

    protected static function _populateFromRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof RevocationRecord);
        assert($entity instanceof RevocationEntity);

        $entity->certificateId = $record->certificateId;
        $entity->serialNumber = $record->serialNumber;
        $entity->revokedAt = $record->revokedAt;
        $entity->reason = $record->reason;
        $entity->caCertificateId = $record->caCertificateId;
    }

    protected static function _populateToRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof RevocationRecord);
        assert($entity instanceof RevocationEntity);

        $record->certificateId = $entity->certificateId;
        $record->serialNumber = $entity->serialNumber;
        $record->revokedAt = $entity->revokedAt;
        $record->reason = $entity->reason;
        $record->caCertificateId = $entity->caCertificateId;
    }
}
