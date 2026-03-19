<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\CertificateSubject\DN;

use KDuma\PhpCA\Record\CertificateSubject\BaseDN;

readonly class EmailAddress extends BaseDN
{
    public static function oid(): string
    {
        return '1.2.840.113549.1.9.1';
    }

    public static function shortName(): string
    {
        return 'emailAddress';
    }
}
