<?php

namespace App\Storage\Repositories;

use App\Storage\Entities\Key;
use App\Storage\Infrastructure\Repository;

class KeyRepository extends Repository
{
    protected function storageName(): string
    {
        return 'keys';
    }

    protected function entityClass(): string
    {
        return Key::class;
    }

    protected function allowedFiles(): array
    {
        return ['private.key', 'public.key'];
    }

    public function forFingerprint(string $fingerprint): ?Key
    {
        return $this->query()->filter(fn (Key $key) => $key->fingerprint === $fingerprint)->first();
    }
}
