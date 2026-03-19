<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

class LiteralResolver extends ExtensionValueResolver
{
    public function __construct(
        public readonly mixed $value,
    ) {}

    public function resolve(IssuanceContext $context): mixed
    {
        return $this->value;
    }

    public static function type(): string
    {
        return 'literal';
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value'] ?? null);
    }

    public function toArray(): string|array
    {
        if (is_string($this->value)) {
            return $this->value;
        }

        return ['type' => self::type(), 'value' => $this->value];
    }
}
