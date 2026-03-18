<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Enum;

enum CertificateAttachment: string
{
    case Certificate = 'certificate.pem';
    case Request = 'request.pem';
}
