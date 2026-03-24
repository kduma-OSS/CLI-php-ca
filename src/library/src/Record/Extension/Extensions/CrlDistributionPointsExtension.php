<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class CrlDistributionPointsExtension extends BaseExtension
{
    /**
     * @param  string[]  $uris
     */
    public function __construct(
        public array $uris,
        private bool $critical = false,
    ) {}

    public static function oid(): string
    {
        return '2.5.29.31';
    }

    public static function name(): string
    {
        return 'crl-distribution-points';
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
            'uris' => $this->uris,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            uris: $data['uris'] ?? [],
            critical: $data['critical'] ?? false,
        );
    }
}
