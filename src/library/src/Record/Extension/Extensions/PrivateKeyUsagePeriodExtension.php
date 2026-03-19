<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use DateTimeImmutable;
use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class PrivateKeyUsagePeriodExtension extends BaseExtension
{
    public function __construct(
        public ?DateTimeImmutable $notBefore = null,
        public ?DateTimeImmutable $notAfter = null,
        private bool $critical = false,
    ) {}

    public static function oid(): string
    {
        return '2.5.29.16';
    }

    public static function name(): string
    {
        return 'private-key-usage-period';
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
            'not_before' => $this->notBefore?->format('c'),
            'not_after' => $this->notAfter?->format('c'),
        ], fn ($v) => $v !== null);
    }

    public static function fromArray(array $data): static
    {
        return new static(
            notBefore: isset($data['not_before']) ? new DateTimeImmutable($data['not_before']) : null,
            notAfter: isset($data['not_after']) ? new DateTimeImmutable($data['not_after']) : null,
            critical: $data['critical'] ?? false,
        );
    }
}
