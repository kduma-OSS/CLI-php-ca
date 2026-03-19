<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\ExtKeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class ExtKeyUsageExtensionTemplate extends BaseExtensionTemplate
{
    public function __construct(
        public readonly array $usages = [],
        public readonly bool $critical = false,
    ) {}

    public static function name(): string
    {
        return 'ext-key-usage';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        return new ExtKeyUsageExtension(
            usages: $this->usages,
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
            'usages' => $this->usages,
        ];
    }

    private const array VALID_USAGES = ['serverAuth', 'clientAuth', 'codeSigning', 'emailProtection', 'timeStamping', 'OCSPSigning'];

    public static function fromArray(array $data): static
    {
        if (isset($data['usages']) && ! is_array($data['usages'])) {
            throw new \InvalidArgumentException('ext-key-usage: "usages" must be an array.');
        }

        foreach (($data['usages'] ?? []) as $usage) {
            if (! is_string($usage)) {
                throw new \InvalidArgumentException('ext-key-usage: each usage must be a string.');
            }
            if (! in_array($usage, self::VALID_USAGES, true)) {
                throw new \InvalidArgumentException("ext-key-usage: unknown usage \"{$usage}\". Valid: " . implode(', ', self::VALID_USAGES));
            }
        }

        if (isset($data['critical']) && ! is_bool($data['critical'])) {
            throw new \InvalidArgumentException('ext-key-usage: "critical" must be a boolean.');
        }

        return new static(
            usages: $data['usages'] ?? [],
            critical: $data['critical'] ?? false,
        );
    }
}
