<?php

namespace App\Storage\Infrastructure;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

abstract class BaseRepository
{
    public function __construct(
        protected Filesystem $files,
        protected string $basePath,
    ) {}

    abstract protected function storageName(): string;

    abstract protected function entityClass(): string;

    abstract protected function allowedFiles(): array;

    protected function validateFilename(string $filename): void
    {
        if (! in_array($filename, $this->allowedFiles(), true)) {
            throw new InvalidArgumentException(
                "File [{$filename}] is not allowed in [{$this->storageName()}]. Allowed: " . implode(', ', $this->allowedFiles()) . "."
            );
        }
    }

    protected function encodeMetadata(array $data): string
    {
        $data = $this->sortArrayKeys($data);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    protected function sortArrayKeys(array $array): array
    {
        ksort($array);

        foreach ($array as $key => $value) {
            if (is_array($value) && ! array_is_list($value)) {
                $array[$key] = $this->sortArrayKeys($value);
            }
        }

        return $array;
    }
}
