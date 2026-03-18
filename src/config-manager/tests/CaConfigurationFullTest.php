<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\CaConfigurationLoader;
use KDuma\PhpCA\ConfigManager\CaConfigurationPersister;
use KDuma\PhpCA\ConfigManager\Integrity\IntegrityConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\EncryptionConfiguration;

it('loads config with all sections', function () {
    $loader = new CaConfigurationLoader();
    $config = $loader->load([
        'adapter' => ['type' => 'directory', 'path' => '/data'],
        'integrity' => [
            'hasher' => ['type' => 'sha256'],
            'on_checksum_failure' => 'throw',
            'on_signature_failure' => 'throw',
            'on_missing_integrity' => 'ignore',
        ],
        'encryption' => [
            'keys' => [
                ['type' => 'secret-box', 'id' => 'master', 'key' => ['type' => 'string', 'value' => 'k']],
            ],
            'rules' => [
                ['key_id' => 'master', 'entity_name' => 'keys'],
            ],
        ],
    ], '/base');

    expect($config->integrity)->toBeInstanceOf(IntegrityConfiguration::class)
        ->and($config->encryption)->toBeInstanceOf(EncryptionConfiguration::class);
});

it('loads config with adapter only', function () {
    $loader = new CaConfigurationLoader();
    $config = $loader->load(['adapter' => ['type' => 'directory', 'path' => '/data']], '/base');

    expect($config->integrity)->toBeNull()
        ->and($config->encryption)->toBeNull();
});

it('round-trips full config', function () {
    $data = [
        'adapter' => ['type' => 'directory', 'path' => '/data'],
        'integrity' => [
            'hasher' => ['type' => 'sha256'],
            'signer' => [
                'type' => 'hmac-sha256',
                'id' => 'signer-1',
                'secret' => 'secret_key',
            ],
            'on_checksum_failure' => 'throw',
            'on_signature_failure' => 'throw',
            'on_missing_integrity' => 'ignore',
            'detached_attachments' => true,
        ],
        'encryption' => [
            'keys' => [
                ['type' => 'secret-box', 'id' => 'master', 'key' => 'enc_key'],
            ],
            'rules' => [
                ['key_id' => 'master', 'entity_name' => 'keys', 'attachment_names' => ['private.key']],
            ],
        ],
    ];

    $loader = new CaConfigurationLoader();
    $config = $loader->load($data, '/unused');

    $persister = new CaConfigurationPersister();
    $output = $persister->toArray($config);

    expect($output)->toBe($data);
});
