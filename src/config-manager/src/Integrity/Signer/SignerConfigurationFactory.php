<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Signer;

use InvalidArgumentException;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\Attributes\SignerConfiguration;
use LogicException;
use olvlvl\ComposerAttributeCollector\Attributes;

class SignerConfigurationFactory
{
    /**
     * @return array<string, class-string<BaseSignerConfiguration>>
     */
    public function getTypes(): array
    {
        $types = [];

        foreach (Attributes::findTargetClasses(SignerConfiguration::class) as $target) {
            $types[$target->attribute->type] = $target->name;
        }

        return $types;
    }

    public function fromArray(array $data, string $basePath): BaseSignerConfiguration
    {
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new InvalidArgumentException('Signer configuration must contain a "type" field.');
        }

        $type = $data['type'];
        $types = $this->getTypes();

        if (! isset($types[$type])) {
            $supported = implode(', ', array_keys($types));
            throw new InvalidArgumentException("Unknown signer type \"{$type}\". Supported types: {$supported}.");
        }

        return $types[$type]::fromArray($data, $basePath);
    }

    /**
     * @param class-string<BaseSignerConfiguration> $class
     */
    public static function getTypeForClass(string $class): string
    {
        $attrs = Attributes::forClass($class);

        foreach ($attrs->classAttributes as $attr) {
            if ($attr instanceof SignerConfiguration) {
                return $attr->type;
            }
        }

        throw new LogicException("Missing #[SignerConfiguration] attribute on {$class}.");
    }
}
