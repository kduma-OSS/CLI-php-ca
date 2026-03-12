<?php

namespace App\Storage;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

abstract class Repository
{
    public function __construct(
        protected Filesystem $files,
        protected string $basePath,
    ) {}

    abstract protected function collection(): string;

    abstract protected function entityClass(): string;

    protected function collectionPath(): string
    {
        return $this->basePath.'/'.$this->collection();
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
            throw new RuntimeException("Entity [{$id}] not found in [{$this->collection()}].");
        }

        return $entity;
    }

    public function save(Entity $entity): void
    {
        $recordPath = $this->recordPath($entity->id);

        if (! $this->files->isDirectory($recordPath)) {
            $this->files->makeDirectory($recordPath, 0755, true);
        }

        $data = $this->sortArrayKeys($entity->toArray());
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";

        $this->files->put($this->metadataPath($entity->id), $json);
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

    public function putFile(string $id, string $filename, string $content): void
    {
        $recordPath = $this->recordPath($id);

        if (! $this->files->isDirectory($recordPath)) {
            $this->files->makeDirectory($recordPath, 0755, true);
        }

        $this->files->put($recordPath.'/'.$filename, $content);
    }

    public function getFile(string $id, string $filename): ?string
    {
        $filePath = $this->recordPath($id).'/'.$filename;

        if (! $this->files->exists($filePath)) {
            return null;
        }

        return $this->files->get($filePath);
    }

    public function hasFile(string $id, string $filename): bool
    {
        return $this->files->exists($this->recordPath($id).'/'.$filename);
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
