<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class ExtKeyUsageExtension extends BaseExtension
{
    /**
     * @param string[] $usages OID short names: serverAuth, clientAuth, codeSigning, emailProtection, timeStamping, OCSPSigning
     */
    public function __construct(
        public array $usages,
        private bool $critical = false,
    ) {}

    public static function oid(): string
    {
        return '2.5.29.37';
    }

    public static function name(): string
    {
        return 'ext-key-usage';
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
            'usages' => $this->usages,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            usages: $data['usages'] ?? [],
            critical: $data['critical'] ?? false,
        );
    }
}
