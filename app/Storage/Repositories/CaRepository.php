<?php

namespace App\Storage\Repositories;

use App\Storage\Entities\CaMetadata;
use App\Storage\Enums\CaFile;
use App\Storage\Infrastructure\SingletonRepository;

class CaRepository extends SingletonRepository
{
    protected function storageName(): string
    {
        return 'ca';
    }

    protected function entityClass(): string
    {
        return CaMetadata::class;
    }

    protected function fileEnum(): string
    {
        return CaFile::class;
    }
}
