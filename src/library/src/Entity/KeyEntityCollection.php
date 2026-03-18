<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\Entity\BaseEntityCollection;

/**
 * @extends BaseEntityCollection<KeyEntity>
 */
class KeyEntityCollection extends BaseEntityCollection
{
    protected string $entityClass {
        get => KeyEntity::class;
    }
}
