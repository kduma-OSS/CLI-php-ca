<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Enum;

enum CrlAttachment: string
{
    case Crl = 'crl.pem';
}
