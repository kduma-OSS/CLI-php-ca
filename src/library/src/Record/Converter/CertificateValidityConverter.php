<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Converter;

use DateTimeImmutable;
use KDuma\PhpCA\Record\CertificateValidity;
use KDuma\SimpleDAL\Typed\Contracts\Converter\FieldConverterInterface;

class CertificateValidityConverter implements FieldConverterInterface
{
    public function fromStorage(mixed $value): mixed
    {
        if (! is_array($value)) {
            return null;
        }

        return new CertificateValidity(
            notBefore: new DateTimeImmutable($value['not_before']),
            notAfter: new DateTimeImmutable($value['not_after']),
        );
    }

    public function toStorage(mixed $value): mixed
    {
        if ($value instanceof CertificateValidity) {
            return [
                'not_before' => $value->notBefore->format('c'),
                'not_after' => $value->notAfter->format('c'),
            ];
        }

        return $value;
    }
}
