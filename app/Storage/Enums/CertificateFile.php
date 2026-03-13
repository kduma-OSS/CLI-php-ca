<?php

namespace App\Storage\Enums;

use App\Storage\Infrastructure\RepositoryFile;

enum CertificateFile: string implements RepositoryFile
{
    case Certificate = 'certificate.pem';
    case Request = 'request.pem';
}
