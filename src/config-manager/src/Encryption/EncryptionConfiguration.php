<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Encryption;

use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\BaseEncryptionAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\EncryptionAlgorithmConfigurationFactory;
use KDuma\SimpleDAL\Encryption\EncryptionConfig;
use KDuma\SimpleDAL\Encryption\EncryptionRule;

readonly class EncryptionConfiguration
{
    /**
     * @param BaseEncryptionAlgorithmConfiguration[] $keys
     * @param EncryptionRuleConfiguration[] $rules
     */
    public function __construct(
        public array $keys,
        public array $rules,
    ) {}

    public function createEncryptionConfig(): EncryptionConfig
    {
        $algorithmKeys = array_map(
            fn (BaseEncryptionAlgorithmConfiguration $key) => $key->createAlgorithm(),
            $this->keys,
        );

        $rules = array_map(
            fn (EncryptionRuleConfiguration $rule) => new EncryptionRule(
                keyId: $rule->keyId,
                entityName: $rule->entityName,
                attachmentNames: $rule->attachmentNames,
                recordIds: $rule->recordIds,
            ),
            $this->rules,
        );

        return new EncryptionConfig(
            keys: $algorithmKeys,
            rules: $rules,
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $algorithmFactory = new EncryptionAlgorithmConfigurationFactory();

        $keys = array_map(
            fn (array $keyData) => $algorithmFactory->fromArray($keyData, $basePath),
            $data['keys'] ?? [],
        );

        $rules = array_map(
            fn (array $ruleData) => EncryptionRuleConfiguration::fromArray($ruleData),
            $data['rules'] ?? [],
        );

        return new static(
            keys: $keys,
            rules: $rules,
        );
    }

    public function toArray(): array
    {
        return [
            'keys' => array_map(fn (BaseEncryptionAlgorithmConfiguration $k) => $k->toArray(), $this->keys),
            'rules' => array_map(fn (EncryptionRuleConfiguration $r) => $r->toArray(), $this->rules),
        ];
    }
}
