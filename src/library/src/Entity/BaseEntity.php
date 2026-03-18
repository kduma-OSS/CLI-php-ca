<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use DateTimeImmutable;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;
use KDuma\SimpleDAL\Typed\Store\TypedAttachmentStore;

/**
 * @template TRecord of TypedRecord
 */
abstract class BaseEntity
{
    public function __construct()
    {}


    /**
     * @internal
     * @param static $entity
     * @param TRecord $record
     */
    abstract static protected function _populateFromRecord(BaseEntity $entity, TypedRecord $record): void;

    /**
     * @internal
     * @param static $entity
     * @param TRecord $record
     */
    abstract static protected function _populateToRecord(BaseEntity $entity, TypedRecord $record): void;

    /**
     * Is the entity already in the database?
     */
    public bool $persisted {
        get => $this->existingRecord !== null;
    }

    public ?string $id = null {
        get {
            return $this->id;
        }
        set {
            if($this->persisted) {
                throw new \LogicException('Cannot set ID on an existing entity.');
            }

            $this->id = $value;
        }
    }

    public ?DateTimeImmutable $createdAt {
        get {
            return $this->existingRecord?->createdAt ?? null;
        }
    }

    public ?DateTimeImmutable $updatedAt {
        get {
            return $this->existingRecord?->updatedAt ?? null;
        }
    }

    /**
     * @var TRecord|null
     */
    private ?TypedRecord $existingRecord = null;
    private ?TypedAttachmentStore $_attachments = null;
    protected TypedAttachmentStore $attachments {
        get {
            if (!$this->persisted) {
                throw new \LogicException('Cannot get attachments on an entity that is not persisted.');
            }

            return $this->_attachments;
        }
    }

    /**
     * @internal
     * @param TRecord $record
     * @param TypedAttachmentStore $attachments
     * @return static
     */
    static public function _fromRecord(TypedRecord $record, TypedAttachmentStore $attachments): static
    {
        $entity = new static();

        $entity->_updateExistingRecord($record, $attachments);

        static::_populateFromRecord($entity, $record);

        return $entity;
    }

    /**
     * @internal
     * @return TRecord
     */
    public function _getUpdatedRecord(): TypedRecord
    {
        if(!$this->persisted) {
            throw new \LogicException('Cannot update a non-existing record');
        }

        $record = clone $this->existingRecord;

        static::_populateToRecord($this, $record);

        return $record;
    }

    /**
     * @internal
     * @param TRecord $record
     * @return TRecord
     */
    public function _getNewRecord(TypedRecord $record): TypedRecord {
        static::_populateToRecord($this, $record);

        return $record;
    }

    /** @internal */
    public function _afterPersisted(): void {

    }

    /**
     * @internal
     * @param TRecord $record
     * @param TypedAttachmentStore $attachments
     */
    public function _updateExistingRecord(TypedRecord $record, TypedAttachmentStore $attachments): void
    {
        /** @noinspection PhpFieldImmediatelyRewrittenInspection */
        $this->existingRecord = null;
        $this->id = $record->id;
        $this->existingRecord = $record;
        $this->_attachments = $attachments;
    }
}
