<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\CertificationAuthority;
use KDuma\SimpleDAL\Typed\Store\TypedCollectionEntity;

/**
 * @extends BaseEntityCollection<CertificateTemplateEntity>
 */
class CertificateTemplateEntityCollection extends BaseEntityCollection
{
    protected string $entityClass {
        get => CertificateTemplateEntity::class;
    }

    public function __construct(
        TypedCollectionEntity $inner,
        private readonly CertificationAuthority $ca,
    ) {
        parent::__construct($inner);
    }

    public function getBuilder(string $id): CertificateTemplateBuilder
    {
        return new CertificateTemplateBuilder($this->ca, $id);
    }
}
