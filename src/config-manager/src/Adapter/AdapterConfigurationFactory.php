<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Adapter;

use InvalidArgumentException;
use KDuma\PhpCA\ConfigManager\Adapter\Attributes\AdapterConfiguration;
use KDuma\PhpCA\ConfigManager\ConfigManagerRegistry;
use LogicException;
use Spatie\Attributes\Attributes;

class AdapterConfigurationFactory
{
    /**
     * @return array<string, class-string<BaseAdapterConfiguration>>
     */
    public function getAdapterTypes(): array
    {
        return ConfigManagerRegistry::getAdapterTypes();
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
     * @param  class-string<BaseAdapterConfiguration>  $class
     */
    public static function getTypeForClass(string $class): string
    {
        $attr = Attributes::get($class, AdapterConfiguration::class);

        if ($attr !== null) {
            return $attr->type;
        }

        throw new LogicException("Missing #[AdapterConfiguration] attribute on {$class}.");
    }
}
