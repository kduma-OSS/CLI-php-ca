<?php

namespace App\Storage\Repositories;

use App\Storage\Entities\Key;
use App\Storage\Repository;

class KeyRepository extends Repository
{
    protected function collection(): string
    {
        return 'keys';
    }

    protected function entityClass(): string
    {
        return Key::class;
    }
}
