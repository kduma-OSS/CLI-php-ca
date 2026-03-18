<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

use InvalidArgumentException;
use KDuma\PhpCA\ConfigManager\ConfigManagerRegistry;
use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;
use LogicException;
use Spatie\Attributes\Attributes;

class ValueProviderFactory
{
    /**
     * @return array<string, class-string<ValueProvider>>
     */
    public function getTypes(): array
    {
        return ConfigManagerRegistry::getValueProviderTypes();
    }

    public function fromArray(string|array $data, string $basePath): ValueProvider
    {
        if (is_string($data)) {
            return new StringValueProvider($data);
        }

        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new InvalidArgumentException('Value provider must contain a "type" field.');
        }

        $type = $data['type'];
        $types = $this->getTypes();

        if (! isset($types[$type])) {
            $supported = implode(', ', array_keys($types));
            throw new InvalidArgumentException("Unknown value provider type \"{$type}\". Supported types: {$supported}.");
        }

        return $types[$type]::fromArray($data, $basePath);
    }

    /**
     * @param class-string<ValueProvider> $class
     */
    public static function getTypeForClass(string $class): string
    {
        $attr = Attributes::get($class, ValueProviderType::class);

        if ($attr !== null) {
            return $attr->type;
        }

        throw new LogicException("Missing #[ValueProviderType] attribute on {$class}.");
    }
}
