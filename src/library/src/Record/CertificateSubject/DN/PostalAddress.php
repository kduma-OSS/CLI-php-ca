<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\CertificateSubject\DN;

use KDuma\PhpCA\Record\CertificateSubject\BaseDN;

readonly class PostalAddress extends BaseDN
{
    public static function oid(): string
    {
        return '2.5.4.16';
    }

    public static function shortName(): string
    {
        return 'postalAddress';
    }
}
