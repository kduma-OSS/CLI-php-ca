<?php

namespace App\Commands\Concerns;

use App\Config\CaConfiguration;
use App\Config\CaConfigurationLoader;
use RuntimeException;

trait LoadsCaConfiguration
{
    protected function getCaConfig(): CaConfiguration
    {
        $loader = app(CaConfigurationLoader::class);
        $path = $this->option('ca');

        if ($path) {
            return $loader->load($path);
        }

        $path = $this->discoverConfigFile();

        if ($path === null) {
            throw new RuntimeException(
                'Configuration file not found. Provide --ca= option or create a php-pki-config.json file.'
            );
        }

        return $loader->load($path);
    }

    protected function discoverConfigFile(): ?string
    {
        $directory = getcwd();

        while (true) {
            $candidate = $directory . '/php-pki-config.json';

            if (file_exists($candidate)) {
                return $candidate;
            }

            $parent = dirname($directory);

            if ($parent === $directory) {
                return null;
            }

            $directory = $parent;
        }
    }
}
