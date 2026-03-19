<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\Record\CaStateRecord;
use KDuma\SimpleDAL\Typed\Store\TypedSingletonEntity;

class CaStateEntity
{
    public function __construct(
        private readonly TypedSingletonEntity $inner,
    ) {}

    public function getActiveCaCertificateId(): ?string
    {
        return $this->getRecord()->activeCaCertificateId ?? null;
    }

    public function setActiveCaCertificateId(?string $id): void
    {
        $record = $this->getOrCreateRecord();
        $record->activeCaCertificateId = $id;
        $this->save($record);
    }

    public function getLastIssuedSequence(): int
    {
        return $this->getRecord()->lastIssuedSequence ?? 0;
    }

    public function nextSequence(): int
    {
        $record = $this->getOrCreateRecord();
        $next = ($record->lastIssuedSequence ?? 0) + 1;
        $record->lastIssuedSequence = $next;
        $this->save($record);

        return $next;
    }

    private function getRecord(): ?CaStateRecord
    {
        return $this->inner->getOrNull();
    }

    private function getOrCreateRecord(): CaStateRecord
    {
        $record = $this->inner->getOrNull();

        if ($record === null) {
            $record = $this->inner->make();
            assert($record instanceof CaStateRecord);
            $record->activeCaCertificateId = null;
            $record->lastIssuedSequence = 0;
        }

        return $record;
    }

    private function save(CaStateRecord $record): void
    {
        if ($this->inner->exists()) {
            $this->inner->save($record);
        } else {
            $this->inner->set($record);
        }
    }
}
