<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Tests\Support;

use KDuma\SimpleDAL\Adapter\Contracts\StorageAdapterInterface;
use KDuma\SimpleDAL\Contracts\EntityDefinitionInterface;
use KDuma\SimpleDAL\Contracts\Exception\AttachmentNotFoundException;
use KDuma\SimpleDAL\Contracts\Exception\RecordNotFoundException;

/**
 * Simple in-memory storage adapter for testing purposes.
 */
final class InMemoryStorageAdapter implements StorageAdapterInterface
{
    /** @var array<string, array<string, array<string, mixed>>> entity => recordId => data */
    private array $records = [];

    /** @var array<string, array<string, array<string, string>>> entity => recordId => name => contents */
    private array $attachments = [];

    /** @var array<string, EntityDefinitionInterface> */
    private array $definitions = [];

    public function initializeEntity(string $entityName, EntityDefinitionInterface $definition): void
    {
        $this->definitions[$entityName] = $definition;

        if (!isset($this->records[$entityName])) {
            $this->records[$entityName] = [];
        }

        if (!isset($this->attachments[$entityName])) {
            $this->attachments[$entityName] = [];
        }
    }

    public function writeRecord(string $entityName, string $recordId, array $data): void
    {
        $this->records[$entityName][$recordId] = $data;
    }

    public function readRecord(string $entityName, string $recordId): array
    {
        if (!isset($this->records[$entityName][$recordId])) {
            throw new RecordNotFoundException("Record '{$recordId}' not found in entity '{$entityName}'.");
        }

        return $this->records[$entityName][$recordId];
    }

    public function deleteRecord(string $entityName, string $recordId): void
    {
        unset($this->records[$entityName][$recordId]);
        unset($this->attachments[$entityName][$recordId]);
    }

    public function recordExists(string $entityName, string $recordId): bool
    {
        return isset($this->records[$entityName][$recordId]);
    }

    public function listRecordIds(string $entityName): array
    {
        if (!isset($this->records[$entityName])) {
            return [];
        }

        $ids = array_keys($this->records[$entityName]);
        sort($ids);

        return $ids;
    }

    public function findRecords(
        string $entityName,
        array $filters = [],
        array $sort = [],
        ?int $limit = null,
        int $offset = 0,
    ): array {
        $results = $this->records[$entityName] ?? [];

        foreach ($filters as $filter) {
            $field = $filter['field'] ?? '';
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            $results = array_filter($results, function (array $data) use ($field, $operator, $value) {
                $fieldValue = $this->resolveFieldValue($data, $field);

                return match ($operator) {
                    '=' => $fieldValue === $value,
                    '!=' => $fieldValue !== $value,
                    default => true,
                };
            });
        }

        if ($offset > 0 || $limit !== null) {
            $results = array_slice($results, $offset, $limit, true);
        }

        return $results;
    }

    public function writeAttachment(string $entityName, string $recordId, string $name, mixed $contents): void
    {
        if (is_resource($contents)) {
            $contents = stream_get_contents($contents);
        }

        $this->attachments[$entityName][$recordId][$name] = $contents;
    }

    public function readAttachment(string $entityName, string $recordId, string $name): mixed
    {
        if (!isset($this->attachments[$entityName][$recordId][$name])) {
            throw new AttachmentNotFoundException("Attachment '{$name}' not found for record '{$recordId}' in entity '{$entityName}'.");
        }

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $this->attachments[$entityName][$recordId][$name]);
        rewind($stream);

        return $stream;
    }

    public function deleteAttachment(string $entityName, string $recordId, string $name): void
    {
        unset($this->attachments[$entityName][$recordId][$name]);
    }

    public function deleteAllAttachments(string $entityName, string $recordId): void
    {
        unset($this->attachments[$entityName][$recordId]);
    }

    public function listAttachments(string $entityName, string $recordId): array
    {
        if (!isset($this->attachments[$entityName][$recordId])) {
            return [];
        }

        $names = array_keys($this->attachments[$entityName][$recordId]);
        sort($names);

        return $names;
    }

    public function attachmentExists(string $entityName, string $recordId, string $name): bool
    {
        return isset($this->attachments[$entityName][$recordId][$name]);
    }

    public function purgeEntity(string $entityName): void
    {
        $this->records[$entityName] = [];
        $this->attachments[$entityName] = [];
    }

    private function resolveFieldValue(array $data, string $field): mixed
    {
        $segments = explode('.', $field);
        $current = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
