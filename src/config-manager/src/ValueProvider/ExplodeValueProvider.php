<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;
use RuntimeException;

#[ValueProviderType('explode')]
readonly class ExplodeValueProvider extends ValueProvider
{
    public function __construct(
        public int $index,
        public string $separator,
        public ValueProvider $value,
    ) {}

    public function resolve(): string
    {
        $resolved = $this->value->resolve();
        $parts = explode($this->separator, $resolved);

        if (! isset($parts[$this->index])) {
            throw new RuntimeException(
                "Explode key discovery: index {$this->index} out of bounds (got " . count($parts) . " parts)."
            );
        }

        return $parts[$this->index];
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory();

        return new static(
            index: $data['index'] ?? throw new \InvalidArgumentException('Explode key discovery requires "index".'),
            separator: $data['separator'] ?? throw new \InvalidArgumentException('Explode key discovery requires "separator".'),
            value: $factory->fromArray($data['value'] ?? throw new \InvalidArgumentException('Explode key discovery requires "value".'), $basePath),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'index' => $this->index,
            'separator' => $this->separator,
            'value' => $this->value->toArray(),
        ];
    }
}
