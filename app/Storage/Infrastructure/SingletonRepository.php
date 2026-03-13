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

    public function putFile(RepositoryFile $file, string $content): void
    {
        $this->validateFilename($file);

        $this->ensureDirectory();

        $this->files->put($this->path().'/'.$file->value, $content);
    }

    public function getFile(RepositoryFile $file): ?string
    {
        $this->validateFilename($file);

        $filePath = $this->path().'/'.$file->value;

        if (! $this->files->exists($filePath)) {
            return null;
        }

        return $this->files->get($filePath);
    }

    public function hasFile(RepositoryFile $file): bool
    {
        $this->validateFilename($file);

        return $this->files->exists($this->path().'/'.$file->value);
    }

    public function deleteFile(RepositoryFile $file): bool
    {
        $this->validateFilename($file);

        $filePath = $this->path().'/'.$file->value;

        if (! $this->files->exists($filePath)) {
            return false;
        }

        return $this->files->delete($filePath);
    }
}
