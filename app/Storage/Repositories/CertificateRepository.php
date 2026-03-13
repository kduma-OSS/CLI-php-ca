<?php

namespace App\Storage\Repositories;

use App\Storage\Entities\Certificate;
use App\Storage\Enums\CertificateFile;
use App\Storage\Infrastructure\Repository;
use Illuminate\Support\Collection;

class CertificateRepository extends Repository
{
    protected function storageName(): string
    {
        return 'certificates';
    }

    protected function entityClass(): string
    {
        return Certificate::class;
    }

    protected function fileEnum(): string
    {
        return CertificateFile::class;
    }

    public function forKey(string $keyId): Collection
    {
        return $this->query()->filter(fn (Certificate $cert) => $cert->keyId === $keyId)->values();
    }
}
