<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Hasher;

use InvalidArgumentException;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Attributes\HasherConfiguration;
use LogicException;
use olvlvl\ComposerAttributeCollector\Attributes;

class HasherConfigurationFactory
{
    /**
     * @return array<string, class-string<BaseHasherConfiguration>>
     */
    public function getTypes(): array
    {
        $types = [];

        foreach (Attributes::findTargetClasses(HasherConfiguration::class) as $target) {
            $types[$target->attribute->type] = $target->name;
        }

        return $types;
    }

    public function fromArray(array $data): BaseHasherConfiguration
    {
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new InvalidArgumentException('Hasher configuration must contain a "type" field.');
        }

        $type = $data['type'];
        $types = $this->getTypes();

        if (! isset($types[$type])) {
            $supported = implode(', ', array_keys($types));
            throw new InvalidArgumentException("Unknown hasher type \"{$type}\". Supported types: {$supported}.");
        }

        return $types[$type]::fromArray($data);
    }

    /**
     * @param class-string<BaseHasherConfiguration> $class
     */
    public static function getTypeForClass(string $class): string
    {
        $attrs = Attributes::forClass($class);

        foreach ($attrs->classAttributes as $attr) {
            if ($attr instanceof HasherConfiguration) {
                return $attr->type;
            }
        }

        throw new LogicException("Missing #[HasherConfiguration] attribute on {$class}.");
    }
}
