<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record;

use DateInterval;
use DateTimeImmutable;

readonly class CertificateValidity
{
    public function __construct(
        public DateTimeImmutable $notBefore,
        public DateTimeImmutable $notAfter,
    ) {}

    public function isValid(?DateTimeImmutable $at = null): bool
    {
        $at ??= new DateTimeImmutable;

        return $at >= $this->notBefore && $at <= $this->notAfter;
    }

    public function isExpired(?DateTimeImmutable $at = null): bool
    {
        $at ??= new DateTimeImmutable;

        return $at > $this->notAfter;
    }

    public function duration(): DateInterval
    {
        return $this->notBefore->diff($this->notAfter);
    }
}
