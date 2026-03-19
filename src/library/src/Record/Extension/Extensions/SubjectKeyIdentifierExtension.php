<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class SubjectKeyIdentifierExtension extends BaseExtension
{
    public function __construct(
        public string $keyIdentifier,
        private bool $critical = false,
    ) {}

    public static function oid(): string
    {
        return '2.5.29.14';
    }

    public static function name(): string
    {
        return 'subject-key-identifier';
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    public function toArray(): array
    {
        return [
            'name' => self::name(),
            'critical' => $this->critical,
            'key_identifier' => $this->keyIdentifier,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            keyIdentifier: $data['key_identifier'],
            critical: $data['critical'] ?? false,
        );
    }
}
