<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Adapter;

use InvalidArgumentException;
use KDuma\PhpCA\ConfigManager\Adapter\Attributes\AdapterConfiguration;
use LogicException;
use olvlvl\ComposerAttributeCollector\Attributes;

class AdapterConfigurationFactory
{
    /**
     * @return array<string, class-string<BaseAdapterConfiguration>>
     */
    public function getAdapterTypes(): array
    {
        $types = [];

        foreach (Attributes::findTargetClasses(AdapterConfiguration::class) as $target) {
            $types[$target->attribute->type] = $target->name;
        }

        return $types;
    }

    public function fromArray(array $data, string $basePath): BaseAdapterConfiguration
    {
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new InvalidArgumentException('Adapter configuration must contain a "type" field.');
        }

        $type = $data['type'];
        $types = $this->getAdapterTypes();

        if (! isset($types[$type])) {
            $supported = implode(', ', array_keys($types));
            throw new InvalidArgumentException("Unknown adapter type \"{$type}\". Supported types: {$supported}.");
        }

        $class = $types[$type];

        return $class::fromArray($data, $basePath);
    }

    /**
     * @param class-string<BaseAdapterConfiguration> $class
     */
    public static function getTypeForClass(string $class): string
    {
        $attrs = Attributes::forClass($class);

        foreach ($attrs->classAttributes as $attr) {
            if ($attr instanceof AdapterConfiguration) {
                return $attr->type;
            }
        }

        throw new LogicException("Missing #[AdapterConfiguration] attribute on {$class}.");
    }
}
