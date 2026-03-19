<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

/**
 * @extends BaseEntityCollection<CsrEntity>
 */
class CsrEntityCollection extends BaseEntityCollection
{
    protected string $entityClass {
        get => CsrEntity::class;
    }
}
