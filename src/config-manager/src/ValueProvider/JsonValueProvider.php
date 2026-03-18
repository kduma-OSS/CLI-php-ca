<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;
use RuntimeException;

#[ValueProviderType('json')]
readonly class JsonValueProvider extends ValueProvider
{
    public function __construct(
        public string $path,
        public ValueProvider $value,
    ) {}

    public function resolve(): string
    {
        $json = $this->value->resolve();
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new RuntimeException('Json key discovery: value is not valid JSON.');
        }

        $segments = explode('.', $this->path);
        $current = $data;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                throw new RuntimeException("Json key discovery: path \"{$this->path}\" not found.");
            }
        }

        if (! is_string($current) && ! is_numeric($current)) {
            throw new RuntimeException("Json key discovery: value at path \"{$this->path}\" is not a string.");
        }

        return (string) $current;
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory();

        return new static(
            path: $data['path'] ?? throw new \InvalidArgumentException('Json key discovery requires "path".'),
            value: $factory->fromArray($data['value'] ?? throw new \InvalidArgumentException('Json key discovery requires "value".'), $basePath),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'path' => $this->path,
            'value' => $this->value->toArray(),
        ];
    }
}
