<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager;

class CaConfigurationPersister
{
    public function toArray(CaConfiguration $configuration): array
    {
        $result = [
            'adapter' => $configuration->adapter->toArray(),
        ];

        if ($configuration->integrity !== null) {
            $result['integrity'] = $configuration->integrity->toArray();
        }

        if ($configuration->encryption !== null) {
            $result['encryption'] = $configuration->encryption->toArray();
        }

        return $result;
    }
}
