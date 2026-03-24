<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;

#[ValueProviderType('base64')]
readonly class Base64ValueProvider extends ValueProvider
{
    public function __construct(
        public string|ValueProvider $value,
    ) {}

    public function resolve(): string
    {
        $encoded = $this->value instanceof ValueProvider
            ? $this->value->resolve()
            : $this->value;

        return base64_decode($encoded, true)
            ?: throw new \RuntimeException('Invalid base64 value in key discovery.');
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $value = $data['value'] ?? throw new \InvalidArgumentException('Base64 key discovery requires "value".');

        if (is_array($value)) {
            $value = (new ValueProviderFactory)->fromArray($value, $basePath);
        }

        return new static(value: $value);
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'value' => $this->value instanceof ValueProvider
                ? $this->value->toArray()
                : $this->value,
        ];
    }
}
