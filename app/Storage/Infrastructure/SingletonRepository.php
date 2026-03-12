<?php

namespace App\Storage\Infrastructure;

abstract class SingletonRepository extends BaseRepository
{
    protected function path(): string
    {
        return $this->basePath.'/'.$this->storageName();
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

        $this->files->put($this->metadataPath(), $this->encodeMetadata($entity->toArray()));
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
}
