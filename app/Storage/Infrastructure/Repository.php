<?php

namespace App\Storage\Infrastructure;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

abstract class Repository extends BaseRepository
{
    protected function collectionPath(): string
    {
        return $this->basePath.'/'.$this->storageName();
    }

    protected function recordPath(string $id): string
    {
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
            throw new InvalidArgumentException("Invalid ID [{$id}]. Only letters, digits, hyphens and underscores are allowed.");
        }

        return $this->collectionPath().'/'.$id;
    }

    protected function metadataPath(string $id): string
    {
        return $this->recordPath($id).'/metadata.json';
    }

    public function all(): Collection
    {
        $path = $this->collectionPath();

        if (! $this->files->isDirectory($path)) {
            return new Collection;
        }

        $directories = $this->files->directories($path);
        $entities = [];

        foreach ($directories as $directory) {
            $id = basename($directory);
            $entity = $this->find($id);

            if ($entity !== null) {
                $entities[] = $entity;
            }
        }

        return new Collection($entities);
    }

    public function find(string $id): ?Entity
    {
        $metadataPath = $this->metadataPath($id);

        if (! $this->files->exists($metadataPath)) {
            return null;
        }

        $data = json_decode($this->files->get($metadataPath), true);
        $class = $this->entityClass();

        return $class::fromArray($id, $data);
    }

    public function findOrFail(string $id): Entity
    {
        $entity = $this->find($id);

        if ($entity === null) {
            throw new RuntimeException("Entity [{$id}] not found in [{$this->storageName()}].");
        }

        return $entity;
    }

    public function save(Entity $entity): void
    {
        $recordPath = $this->recordPath($entity->id);

        if (! $this->files->isDirectory($recordPath)) {
            $this->files->makeDirectory($recordPath, 0755, true);
        }

        $this->files->put($this->metadataPath($entity->id), $this->encodeMetadata($entity->toArray()));
    }

    public function delete(string $id): bool
    {
        $recordPath = $this->recordPath($id);

        if (! $this->files->isDirectory($recordPath)) {
            return false;
        }

        return $this->files->deleteDirectory($recordPath);
    }

    public function exists(string $id): bool
    {
        return $this->files->exists($this->metadataPath($id));
    }

    public function query(): Collection
    {
        return $this->all();
    }

    public function putFile(string $id, RepositoryFile $file, string $content): void
    {
        $this->validateFilename($file);

        $recordPath = $this->recordPath($id);

        if (! $this->files->isDirectory($recordPath)) {
            $this->files->makeDirectory($recordPath, 0755, true);
        }

        $this->files->put($recordPath.'/'.$file->value, $content);
    }

    public function getFile(string $id, RepositoryFile $file): ?string
    {
        $this->validateFilename($file);

        $filePath = $this->recordPath($id).'/'.$file->value;

        if (! $this->files->exists($filePath)) {
            return null;
        }

        return $this->files->get($filePath);
    }

    public function hasFile(string $id, RepositoryFile $file): bool
    {
        $this->validateFilename($file);

        return $this->files->exists($this->recordPath($id).'/'.$file->value);
    }
}
