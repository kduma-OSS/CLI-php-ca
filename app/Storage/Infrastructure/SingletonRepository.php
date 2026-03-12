<?php

namespace App\Storage\Infrastructure;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

abstract class SingletonRepository
{
    public function __construct(
        protected Filesystem $files,
        protected string $basePath,
    ) {}

    abstract protected function directory(): string;

    abstract protected function entityClass(): string;

    abstract protected function allowedFiles(): array;

    protected function path(): string
    {
        return $this->basePath.'/'.$this->directory();
    }

    protected function metadataPath(): string
    {
        return $this->path().'/metadata.json';
    }

    protected function ensureDirectory(): void
    {
        if (! $this->files->isDirectory($this->path())) {
            $this->files->makeDirectory($this->path(), 0755, true);
        }
    }

    public function metadata(): ?SingletonEntity
    {
        $path = $this->metadataPath();

        if (! $this->files->exists($path)) {
            return null;
        }

        $data = json_decode($this->files->get($path), true);
        $class = $this->entityClass();

        return $class::fromArray($data);
    }

    public function saveMetadata(SingletonEntity $entity): void
    {
        $this->ensureDirectory();

        $data = $this->sortArrayKeys($entity->toArray());
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";

        $this->files->put($this->metadataPath(), $json);
    }

    private function validateFilename(string $filename): void
    {
        if (! in_array($filename, $this->allowedFiles(), true)) {
            throw new InvalidArgumentException(
                "File [{$filename}] is not allowed in [{$this->directory()}]. Allowed: " . implode(', ', $this->allowedFiles()) . "."
            );
        }
    }

    public function putFile(string $filename, string $content): void
    {
        $this->validateFilename($filename);

        $this->ensureDirectory();

        $this->files->put($this->path().'/'.$filename, $content);
    }

    public function getFile(string $filename): ?string
    {
        $this->validateFilename($filename);

        $filePath = $this->path().'/'.$filename;

        if (! $this->files->exists($filePath)) {
            return null;
        }

        return $this->files->get($filePath);
    }

    public function hasFile(string $filename): bool
    {
        $this->validateFilename($filename);

        return $this->files->exists($this->path().'/'.$filename);
    }

    public function deleteFile(string $filename): bool
    {
        $this->validateFilename($filename);

        $filePath = $this->path().'/'.$filename;

        if (! $this->files->exists($filePath)) {
            return false;
        }

        return $this->files->delete($filePath);
    }

    private function sortArrayKeys(array $array): array
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
