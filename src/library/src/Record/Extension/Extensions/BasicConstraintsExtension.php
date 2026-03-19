<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class BasicConstraintsExtension extends BaseExtension
{
    public function __construct(
        public bool $ca = false,
        public ?int $pathLenConstraint = null,
        private bool $critical = true,
    ) {}

    public static function oid(): string
    {
        return '2.5.29.19';
    }

    public static function name(): string
    {
        return 'basic-constraints';
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
            'ca' => $this->ca,
            'path_len_constraint' => $this->pathLenConstraint,
        ], fn ($v) => $v !== null);
    }

    public static function fromArray(array $data): static
    {
        return new static(
            ca: $data['ca'] ?? false,
            pathLenConstraint: $data['path_len_constraint'] ?? null,
            critical: $data['critical'] ?? true,
        );
    }
}
