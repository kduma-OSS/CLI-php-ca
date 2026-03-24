<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

/**
 * @extends BaseEntityCollection<KeyEntity>
 */
class KeyEntityCollection extends BaseEntityCollection
{
    protected string $entityClass {
        get => KeyEntity::class;
    }
}
