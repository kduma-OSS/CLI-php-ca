<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\CrlDistributionPointsExtension;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolverFactory;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class CrlDistributionPointsExtensionTemplate extends BaseExtensionTemplate
{
    public function __construct(
        public readonly mixed $uris = [],
        public readonly bool $critical = false,
    ) {}

    public static function name(): string
    {
        return 'crl-distribution-points';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        return new CrlDistributionPointsExtension(
            uris: ExtensionValueResolverFactory::resolveField($this->uris, $context),
            critical: $this->critical,
        );
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
            'uris' => ExtensionValueResolverFactory::toMixed($this->uris),
        ];
    }

    public static function fromArray(array $data): static
    {
        if (isset($data['critical']) && ! is_bool($data['critical'])) {
            throw new \InvalidArgumentException('crl-distribution-points: "critical" must be a boolean.');
        }

        if (isset($data['uris']) && ! is_array($data['uris'])) {
            throw new \InvalidArgumentException('crl-distribution-points: "uris" must be an array.');
        }

        return new static(
            uris: isset($data['uris']) ? ExtensionValueResolverFactory::fromMixed($data['uris']) : [],
            critical: $data['critical'] ?? false,
        );
    }
}
