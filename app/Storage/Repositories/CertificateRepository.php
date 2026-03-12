<?php

namespace App\Storage\Repositories;

use App\Storage\Entities\Certificate;
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

    protected function allowedFiles(): array
    {
        return ['certificate.pem', 'request.pem'];
    }

    public function forKey(string $keyId): Collection
    {
        return $this->query()->filter(fn (Certificate $cert) => $cert->keyId === $keyId)->values();
    }
}
