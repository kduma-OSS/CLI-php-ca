<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class AuthorityKeyIdentifierExtension extends BaseExtension
{
    public function __construct(
        public string $keyIdentifier,
        public ?string $certIssuer = null,
        public ?string $certSerialNumber = null,
        private bool $critical = false,
    ) {}

    public static function oid(): string
    {
        return '2.5.29.35';
    }

    public static function name(): string
    {
        return 'authority-key-identifier';
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => self::name(),
            'critical' => $this->critical,
            'key_identifier' => $this->keyIdentifier,
            'cert_issuer' => $this->certIssuer,
            'cert_serial_number' => $this->certSerialNumber,
        ], fn ($v) => $v !== null);
    }

    public static function fromArray(array $data): static
    {
        return new static(
            keyIdentifier: $data['key_identifier'],
            certIssuer: $data['cert_issuer'] ?? null,
            certSerialNumber: $data['cert_serial_number'] ?? null,
            critical: $data['critical'] ?? false,
        );
    }
}
