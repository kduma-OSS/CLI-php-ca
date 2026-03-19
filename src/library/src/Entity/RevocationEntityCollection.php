<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\SimpleDAL\Query\Filter;

/**
 * @extends BaseEntityCollection<RevocationEntity>
 */
class RevocationEntityCollection extends BaseEntityCollection
{
    protected string $entityClass {
        get => RevocationEntity::class;
    }

    /**
     * @return RevocationEntity[]
     */
    public function forCertificate(string $certificateId): array
    {
        return $this->filter(
            Filter::where('certificate_id', '=', $certificateId),
        );
    }
}
