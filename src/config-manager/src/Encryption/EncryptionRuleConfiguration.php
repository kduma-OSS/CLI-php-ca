<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Encryption;

readonly class EncryptionRuleConfiguration
{
    /**
     * @param string[]|null $attachmentNames
     * @param string[]|null $recordIds
     */
    public function __construct(
        public string $keyId,
        public string $entityName,
        public ?array $attachmentNames = null,
        public ?array $recordIds = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            keyId: $data['key_id'] ?? throw new \InvalidArgumentException('Encryption rule requires "key_id".'),
            entityName: $data['entity_name'] ?? throw new \InvalidArgumentException('Encryption rule requires "entity_name".'),
            attachmentNames: $data['attachment_names'] ?? null,
            recordIds: $data['record_ids'] ?? null,
        );
    }

    public function toArray(): array
    {
        $result = [
            'key_id' => $this->keyId,
            'entity_name' => $this->entityName,
        ];

        if ($this->attachmentNames !== null) {
            $result['attachment_names'] = $this->attachmentNames;
        }

        if ($this->recordIds !== null) {
            $result['record_ids'] = $this->recordIds;
        }

        return $result;
    }
}
