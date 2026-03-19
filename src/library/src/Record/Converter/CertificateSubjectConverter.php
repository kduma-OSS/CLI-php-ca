<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Converter;

use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\SimpleDAL\Typed\Contracts\Converter\FieldConverterInterface;

class CertificateSubjectConverter implements FieldConverterInterface
{
    public function fromStorage(mixed $value): mixed
    {
        if (! is_array($value)) {
            return null;
        }

        return CertificateSubject::fromArray($value);
    }

    public function toStorage(mixed $value): mixed
    {
        if ($value instanceof CertificateSubject) {
            return $value->toArray();
        }

        return $value;
    }
}
