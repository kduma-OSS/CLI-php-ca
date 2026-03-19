<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\CertificationAuthority;
use KDuma\SimpleDAL\Typed\Store\TypedCollectionEntity;

/**
 * @extends BaseEntityCollection<CrlEntity>
 */
class CrlEntityCollection extends BaseEntityCollection
{
    protected string $entityClass {
        get => CrlEntity::class;
    }

    public function __construct(
        TypedCollectionEntity $inner,
        private readonly CertificationAuthority $ca,
    ) {
        parent::__construct($inner);
    }

    public function getBuilder(): CrlBuilder
    {
        return new CrlBuilder($this->ca);
    }
}
