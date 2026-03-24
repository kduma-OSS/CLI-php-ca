<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\SubjectKeyIdentifierExtension;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolver;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolverFactory;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Resolver\SubjectKeyFingerprintResolver;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class SubjectKeyIdentifierExtensionTemplate extends BaseExtensionTemplate
{
    public function __construct(
        public readonly ExtensionValueResolver $keyIdentifier,
        public readonly bool $critical = false,
    ) {}

    public static function name(): string
    {
        return 'subject-key-identifier';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        return new SubjectKeyIdentifierExtension(
            keyIdentifier: $this->keyIdentifier->resolve($context),
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
            'key_identifier' => $this->keyIdentifier->toArray(),
        ];
    }

    public static function fromArray(array $data): static
    {
        if (isset($data['critical']) && ! is_bool($data['critical'])) {
            throw new \InvalidArgumentException('subject-key-identifier: "critical" must be a boolean.');
        }

        $resolver = isset($data['key_identifier'])
            ? ExtensionValueResolverFactory::fromMixed($data['key_identifier'])
            : new SubjectKeyFingerprintResolver;

        if (! $resolver instanceof ExtensionValueResolver) {
            throw new \InvalidArgumentException('subject-key-identifier: "key_identifier" must be a string or resolver object.');
        }

        return new static(
            keyIdentifier: $resolver,
            critical: $data['critical'] ?? false,
        );
    }
}
