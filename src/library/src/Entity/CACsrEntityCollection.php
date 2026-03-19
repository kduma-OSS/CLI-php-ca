<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\CertificationAuthority;
use KDuma\SimpleDAL\Typed\Store\TypedCollectionEntity;

/**
 * @extends BaseEntityCollection<CACsrEntity>
 */
class CACsrEntityCollection extends BaseEntityCollection
{
    protected string $entityClass {
        get => CACsrEntity::class;
    }

    public function __construct(
        TypedCollectionEntity $inner,
        private readonly CertificationAuthority $ca,
    ) {
        parent::__construct($inner);
    }

    public function getBuilder(): CACsrBuilder
    {
        return new CACsrBuilder($this->ca);
    }
}
