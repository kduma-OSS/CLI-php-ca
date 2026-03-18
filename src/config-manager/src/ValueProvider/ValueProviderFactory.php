<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

use InvalidArgumentException;
use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;
use LogicException;
use olvlvl\ComposerAttributeCollector\Attributes;

class ValueProviderFactory
{
    /**
     * @return array<string, class-string<ValueProvider>>
     */
    public function getTypes(): array
    {
        $types = [];

        foreach (Attributes::findTargetClasses(ValueProviderType::class) as $target) {
            $types[$target->attribute->type] = $target->name;
        }

        return $types;
    }

    public function fromArray(string|array $data, string $basePath): ValueProvider
    {
        if (is_string($data)) {
            return new StringValueProvider($data);
        }

        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new InvalidArgumentException('Key discovery must contain a "type" field.');
        }

        $type = $data['type'];
        $types = $this->getTypes();

        if (! isset($types[$type])) {
            $supported = implode(', ', array_keys($types));
            throw new InvalidArgumentException("Unknown key discovery type \"{$type}\". Supported types: {$supported}.");
        }

        return $types[$type]::fromArray($data, $basePath);
    }

    /**
     * @param class-string<ValueProvider> $class
     */
    public static function getTypeForClass(string $class): string
    {
        $attrs = Attributes::forClass($class);

        foreach ($attrs->classAttributes as $attr) {
            if ($attr instanceof ValueProviderType) {
                return $attr->type;
            }
        }

        throw new LogicException("Missing #[ValueProviderType] attribute on {$class}.");
    }
}
