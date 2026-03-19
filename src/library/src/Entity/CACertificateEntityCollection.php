<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\CertificationAuthority;
use KDuma\SimpleDAL\Typed\Store\TypedCollectionEntity;

/**
 * @extends BaseEntityCollection<CACertificateEntity>
 */
class CACertificateEntityCollection extends BaseEntityCollection
{
    protected string $entityClass {
        get => CACertificateEntity::class;
    }

    public function __construct(
        TypedCollectionEntity $inner,
        private readonly CertificationAuthority $ca,
    ) {
        parent::__construct($inner);
    }

    public function getBuilder(): CACertificateBuilder
    {
        return new CACertificateBuilder($this->ca);
    }
}
