<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;

#[ValueProviderType('string')]
readonly class StringValueProvider extends ValueProvider
{
    public function __construct(
        public string $value,
    ) {}

    public function resolve(): string
    {
        return $this->value;
    }

    public static function fromArray(array $data, string $basePath): static
    {
        return new static(
            value: $data['value'] ?? throw new \InvalidArgumentException('String key discovery requires "value".'),
        );
    }

    public function toArray(): string
    {
        return $this->value;
    }
}
