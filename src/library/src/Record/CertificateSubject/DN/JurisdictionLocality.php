<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\CertificateSubject\DN;

use KDuma\PhpCA\Record\CertificateSubject\BaseDN;

readonly class JurisdictionLocality extends BaseDN
{
    public static function oid(): string
    {
        return '1.3.6.1.4.1.311.60.2.1.1';
    }

    public static function shortName(): string
    {
        return 'jurisdictionL';
    }
}
