<?php

namespace App\Storage\Enums;

use App\Storage\Infrastructure\RepositoryFile;

enum CaFile: string implements RepositoryFile
{
    case Certificate = 'certificate.crt';
    case Csr = 'csr.req';
}
