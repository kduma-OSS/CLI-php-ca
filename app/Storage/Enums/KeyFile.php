<?php

namespace App\Storage\Enums;

use App\Storage\Infrastructure\RepositoryFile;

enum KeyFile: string implements RepositoryFile
{
    case PrivateKey = 'private.key';
    case PublicKey = 'public.key';
}
