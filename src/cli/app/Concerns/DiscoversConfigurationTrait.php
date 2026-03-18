<?php

namespace App\Concerns;

use KDuma\PhpCA\ConfigManager\CaConfiguration;
use KDuma\PhpCA\ConfigManager\CaConfigurationLoader;
use KDuma\SimpleDAL\Adapter\Contracts\StorageAdapterInterface;
use Symfony\Component\Console\Input\InputOption;

trait DiscoversConfigurationTrait
{
    const string CONFIG_FILE_NAME = 'php-ca-config.json';

    protected function bootDiscoversConfigurationTrait(): void
    {
        $option = new InputOption(
            name: 'ca-config-file',
            shortcut: 'c',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Path to CA configuration file',
            default: null
        );

        $this->getDefinition()->addOption($option);
    }

    protected function getCaConfigPath(): string
    {
        $path = $this->option('ca-config-file');

        if (!$path) {
            $path = $this->discoverConfigFile();
        }

        if (!file_exists($path)) {
            throw new \InvalidArgumentException("CA configuration file not found at: $path");
        }

        return realpath($path);
    }

    protected function getCaConfiguration(): CaConfiguration
    {
        $path = $this->getCaConfigPath();
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $loader = new CaConfigurationLoader();

        return $loader->load($data, dirname($path));
    }

    protected function getCaAdapter(): StorageAdapterInterface
    {
        return $this->getCaConfiguration()->adapter->createAdapter();
    }

    protected function discoverConfigFile(): string
    {
        $directory = getcwd();

        while (true) {
            $candidate = $directory . '/' . self::CONFIG_FILE_NAME;

            if (file_exists($candidate)) {
                return $candidate;
            }

            $parent = dirname($directory);

            if ($parent === $directory) {
                throw new \InvalidArgumentException("CA configuration file not found");
            }

            $directory = $parent;
        }
    }
}
