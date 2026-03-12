<?php

namespace App\Config;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class CaConfigurationLoader
{
    public function __construct(
        private Filesystem $files,
    ) {}

    public function load(string $path): CaConfiguration
    {
        if (! $this->files->exists($path)) {
            throw new RuntimeException("Configuration file not found: {$path}");
        }

        $contents = $this->files->get($path);
        $data = json_decode($contents, true);

        if (! is_array($data)) {
            throw new RuntimeException("Invalid JSON in configuration file: {$path}");
        }

        $basePath = dirname($this->files->isFile($path) ? realpath($path) : $path);

        return CaConfiguration::fromArray($basePath, $data);
    }
}
