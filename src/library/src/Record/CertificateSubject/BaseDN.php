<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\CertificateSubject;

abstract readonly class BaseDN
{
    public function __construct(
        public string $value,
    ) {}

    abstract public static function oid(): string;

    abstract public static function shortName(): string;

    public function toArray(): array
    {
        return [
            'type' => static::shortName(),
            'value' => $this->value,
        ];
    }
}
