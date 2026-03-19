<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\CertificationAuthority;
use KDuma\SimpleDAL\Typed\Store\TypedCollectionEntity;

/**
 * @extends BaseEntityCollection<CertificateEntity>
 */
class CertificateEntityCollection extends BaseEntityCollection
{
    protected string $entityClass {
        get => CertificateEntity::class;
    }

    public function __construct(
        TypedCollectionEntity $inner,
        private readonly CertificationAuthority $ca,
    ) {
        parent::__construct($inner);
    }

    public function getBuilder(): CertificateBuilder
    {
        return new CertificateBuilder($this->ca);
    }
}
