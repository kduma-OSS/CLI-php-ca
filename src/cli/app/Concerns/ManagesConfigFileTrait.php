<?php

namespace App\Concerns;

use KDuma\PhpCA\ConfigManager\CaConfigurationLoader;

use function Laravel\Prompts\warning;

trait ManagesConfigFileTrait
{
    protected function readConfigData(): array
    {
        $path = $this->getCaConfigPath();

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function writeConfigData(array $data): void
    {
        $path = $this->getCaConfigPath();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";

        file_put_contents($path, $json);
    }

    protected function validateConfig(array $data): bool
    {
        try {
            $loader = new CaConfigurationLoader;
            $loader->load($data, dirname($this->getCaConfigPath()));

            return true;
        } catch (\Throwable $e) {
            warning("Warning: {$e->getMessage()}");

            return false;
        }
    }

    protected function getByDotPath(array $data, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return null;
            }
        }

        return $current;
    }

    protected function hasByDotPath(array $data, string $path): bool
    {
        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    protected function setByDotPath(array &$data, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $current = &$data;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;
            } else {
                if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
    }

    protected function unsetByDotPath(array &$data, string $path): bool
    {
        $segments = explode('.', $path);
        $current = &$data;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                if (array_key_exists($segment, $current)) {
                    unset($current[$segment]);

                    return true;
                }

                return false;
            }

            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                return false;
            }

            $current = &$current[$segment];
        }

        return false;
    }

    protected function parseValue(string $value): mixed
    {
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }
}
