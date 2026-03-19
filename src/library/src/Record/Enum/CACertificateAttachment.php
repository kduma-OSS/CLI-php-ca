<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Enum;

enum CACertificateAttachment: string
{
    case Certificate = 'certificate.pem';
    case Chain = 'chain.pem';
}
