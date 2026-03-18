<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\SimpleDAL\Contracts\Query\FilterInterface;
use KDuma\SimpleDAL\Contracts\RecordInterface;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;
use KDuma\SimpleDAL\Typed\Store\TypedCollectionEntity;
use KDuma\SimpleDAL\Typed\TypedRecordHydrator;

/**
 * @template TEntity of BaseEntity
 */
abstract class BaseEntityCollection
{
    /**
     * @var class-string<TEntity>
     */
    abstract protected string $entityClass {
        get;
    }

    public function __construct(protected TypedCollectionEntity $inner)
    {
    }

    /**
     * @return TEntity
     */
    public function find(string $id): BaseEntity
    {
        $record = $this->inner->find($id);

        return $this->wrapRecord($record);
    }

    /**
     * @return ?TEntity
     */
    public function findOrNull(string $id): ?BaseEntity
    {
        $record = $this->inner->findOrNull($id);

        if ($record === null) {
            return null;
        }

        return $this->wrapRecord($record);
    }

    public function has(string $id): bool
    {
        return $this->inner->has($id);
    }

    public function all(): array
    {
        $records = $this->inner->all();

        return array_map(fn (TypedRecord $record) => $this->wrapRecord($record), $records);
    }

    public function filter(FilterInterface $filter): array
    {
        $records = $this->inner->filter($filter);

        return array_map(fn (TypedRecord $record) => $this->wrapRecord($record), $records);
    }

    /**
     * @param TEntity $entity
     */
    public function save(BaseEntity $entity): void
    {
        if (!$entity->persisted) {
            $record = $entity->_getNewRecord($this->inner->make());
            $record = $this->inner->create($record, $entity->id);
        } else {
            $record = $entity->_getUpdatedRecord();
            $record = $this->inner->save($record);
        }

        $entity->_updateExistingRecord($record, $this->inner->attachments($record->id));
        $entity->_afterPersisted();
    }

    public function delete(string $id): void
    {
        $this->inner->delete($id);
    }

    public function count(?FilterInterface $filter = null): int
    {
        return $this->inner->count($filter);
    }

    /**
     * @param TypedRecord $record
     * @return TEntity
     */
    protected function wrapRecord(TypedRecord $record): BaseEntity
    {
        return $this->entityClass::_fromRecord($record, $this->inner->attachments($record->id));
    }
}
