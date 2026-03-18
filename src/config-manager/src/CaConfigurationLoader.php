<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager;

use InvalidArgumentException;
use KDuma\PhpCA\ConfigManager\Adapter\AdapterConfigurationFactory;
use KDuma\PhpCA\ConfigManager\Encryption\EncryptionConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\IntegrityConfiguration;

class CaConfigurationLoader
{
    public function load(array $data, string $basePath): CaConfiguration
    {
        if (! isset($data['adapter']) || ! is_array($data['adapter'])) {
            throw new InvalidArgumentException('Configuration must contain an "adapter" section.');
        }

        $factory = new AdapterConfigurationFactory();
        $adapter = $factory->fromArray($data['adapter'], $basePath);

        $integrity = null;
        if (isset($data['integrity']) && is_array($data['integrity'])) {
            $integrity = IntegrityConfiguration::fromArray($data['integrity'], $basePath);
        }

        $encryption = null;
        if (isset($data['encryption']) && is_array($data['encryption'])) {
            $encryption = EncryptionConfiguration::fromArray($data['encryption'], $basePath);
        }

        return new CaConfiguration(
            adapter: $adapter,
            integrity: $integrity,
            encryption: $encryption,
        );
    }
}
