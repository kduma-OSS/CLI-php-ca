<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\PrivateKeyUsagePeriodExtension;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolver;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolverFactory;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Resolver\RelativeDateResolver;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class PrivateKeyUsagePeriodExtensionTemplate extends BaseExtensionTemplate
{
    public function __construct(
        public readonly ?ExtensionValueResolver $notBefore = null,
        public readonly ?ExtensionValueResolver $notAfter = null,
        public readonly bool $critical = false,
    ) {}

    public static function name(): string
    {
        return 'private-key-usage-period';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        return new PrivateKeyUsagePeriodExtension(
            notBefore: $this->notBefore?->resolve($context),
            notAfter: $this->notAfter?->resolve($context),
            critical: $this->critical,
        );
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
            'not_before' => $this->notBefore?->toArray(),
            'not_after' => $this->notAfter?->toArray(),
        ], fn ($v) => $v !== null);
    }

    public static function fromArray(array $data): static
    {
        if (isset($data['critical']) && ! is_bool($data['critical'])) {
            throw new \InvalidArgumentException('private-key-usage-period: "critical" must be a boolean.');
        }

        $notBefore = isset($data['not_before'])
            ? self::parseResolver($data['not_before'])
            : null;

        $notAfter = isset($data['not_after'])
            ? self::parseResolver($data['not_after'])
            : null;

        if ($notBefore === null && $notAfter === null) {
            throw new \InvalidArgumentException('private-key-usage-period: at least one of "not_before" or "not_after" is required.');
        }

        return new static(
            notBefore: $notBefore,
            notAfter: $notAfter,
            critical: $data['critical'] ?? false,
        );
    }

    private static function parseResolver(mixed $data): ExtensionValueResolver
    {
        if (is_array($data) && isset($data['type'])) {
            return ExtensionValueResolverFactory::fromArray($data);
        }

        // Default: relative-date with base=not-before or not-after
        return new RelativeDateResolver(base: 'not-before');
    }
}
