<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;

#[ValueProviderType('env')]
readonly class EnvValueProvider extends ValueProvider
{
    public function __construct(
        public string $variable,
    ) {}

    public function resolve(): string
    {
        $value = getenv($this->variable);

        if ($value === false) {
            throw new \RuntimeException("Environment variable \"{$this->variable}\" is not set.");
        }

        return $value;
    }

    public static function fromArray(array $data, string $basePath): static
    {
        return new static(
            variable: $data['variable'] ?? throw new \InvalidArgumentException('Env key discovery requires "variable".'),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'variable' => $this->variable,
        ];
    }
}
