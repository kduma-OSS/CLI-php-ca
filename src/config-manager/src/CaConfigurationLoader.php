<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager;

use InvalidArgumentException;
use KDuma\PhpCA\ConfigManager\Adapter\AdapterConfigurationFactory;

class CaConfigurationLoader
{
    public function load(array $data, string $basePath): CaConfiguration
    {
        if (! isset($data['adapter']) || ! is_array($data['adapter'])) {
            throw new InvalidArgumentException('Configuration must contain an "adapter" section.');
        }

        $factory = new AdapterConfigurationFactory();
        $adapter = $factory->fromArray($data['adapter'], $basePath);

        return new CaConfiguration(
            adapter: $adapter,
        );
    }
}
