<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

class SubjectKeyFingerprintResolver extends ExtensionValueResolver
{
    public function resolve(IssuanceContext $context): string
    {
        return $context->subjectKey->fingerprint;
    }

    public static function type(): string
    {
        return 'subject-key-fingerprint';
    }

    public static function fromArray(array $data): static
    {
        return new static;
    }

    public function toArray(): array
    {
        return ['type' => self::type()];
    }
}
