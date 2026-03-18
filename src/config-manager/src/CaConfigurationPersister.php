<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager;

class CaConfigurationPersister
{
    public function toArray(CaConfiguration $configuration): array
    {
        return [
            'adapter' => $configuration->adapter->toArray(),
        ];
    }
}
