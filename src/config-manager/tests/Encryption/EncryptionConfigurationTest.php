<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\SecretBoxAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\EncryptionConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\EncryptionRuleConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\StringValueProvider;
use KDuma\SimpleDAL\Encryption\EncryptionConfig;

it('loads from array', function () {
    $config = EncryptionConfiguration::fromArray([
        'keys' => [
            [
                'type' => 'secret-box',
                'id' => 'master',
                'key' => ['type' => 'string', 'value' => str_repeat('a', 32)],
            ],
        ],
        'rules' => [
            [
                'key_id' => 'master',
                'entity_name' => 'keys',
                'attachment_names' => ['private.key'],
            ],
        ],
    ], '/base');

    expect($config->keys)->toHaveCount(1)
        ->and($config->keys[0])->toBeInstanceOf(SecretBoxAlgorithmConfiguration::class)
        ->and($config->rules)->toHaveCount(1)
        ->and($config->rules[0]->keyId)->toBe('master')
        ->and($config->rules[0]->entityName)->toBe('keys')
        ->and($config->rules[0]->attachmentNames)->toBe(['private.key']);
});

it('round-trips encryption configuration', function () {
    $config = EncryptionConfiguration::fromArray([
        'keys' => [
            [
                'type' => 'secret-box',
                'id' => 'master',
                'key' => 'my_key',
            ],
        ],
        'rules' => [
            [
                'key_id' => 'master',
                'entity_name' => 'keys',
                'attachment_names' => ['private.key'],
            ],
        ],
    ], '/base');

    expect($config->toArray())->toBe([
        'keys' => [
            [
                'type' => 'secret-box',
                'id' => 'master',
                'key' => 'my_key',
            ],
        ],
        'rules' => [
            [
                'key_id' => 'master',
                'entity_name' => 'keys',
                'attachment_names' => ['private.key'],
            ],
        ],
    ]);
});

it('creates EncryptionConfig object', function () {
    $config = new EncryptionConfiguration(
        keys: [
            new SecretBoxAlgorithmConfiguration(
                id: 'master',
                key: new StringValueProvider(str_repeat('a', 32)),
            ),
        ],
        rules: [
            new EncryptionRuleConfiguration(
                keyId: 'master',
                entityName: 'keys',
            ),
        ],
    );

    $encConfig = $config->createEncryptionConfig();

    expect($encConfig)->toBeInstanceOf(EncryptionConfig::class);
});

it('handles empty keys and rules', function () {
    $config = EncryptionConfiguration::fromArray([], '/base');

    expect($config->keys)->toBe([])
        ->and($config->rules)->toBe([]);
});
