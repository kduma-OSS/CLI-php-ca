<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Integrity\IntegrityConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha256SignerConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\StringValueProvider;
use KDuma\SimpleDAL\Integrity\FailureMode;

it('loads from array with hasher only', function () {
    $config = IntegrityConfiguration::fromArray([
        'hasher' => ['type' => 'sha256'],
    ], '/base');

    expect($config->hasher)->toBeInstanceOf(Sha256HasherConfiguration::class)
        ->and($config->signer)->toBeNull()
        ->and($config->onChecksumFailure)->toBe(FailureMode::Throw)
        ->and($config->onSignatureFailure)->toBe(FailureMode::Throw)
        ->and($config->onMissingIntegrity)->toBe(FailureMode::Throw)
        ->and($config->detachedAttachments)->toBeTrue();
});

it('loads from array with custom failure modes', function () {
    $config = IntegrityConfiguration::fromArray([
        'hasher' => ['type' => 'sha256'],
        'on_checksum_failure' => 'throw',
        'on_signature_failure' => 'ignore',
        'on_missing_integrity' => 'ignore',
    ], '/base');

    expect($config->onChecksumFailure)->toBe(FailureMode::Throw)
        ->and($config->onSignatureFailure)->toBe(FailureMode::Ignore)
        ->and($config->onMissingIntegrity)->toBe(FailureMode::Ignore);
});

it('round-trips integrity configuration', function () {
    $config = IntegrityConfiguration::fromArray([
        'hasher' => ['type' => 'sha256'],
        'signer' => [
            'type' => 'hmac-sha256',
            'id' => 'signer-1',
            'secret' => 'key',
        ],
        'on_checksum_failure' => 'throw',
        'on_signature_failure' => 'throw',
        'on_missing_integrity' => 'ignore',
    ], '/base');

    expect($config->toArray())->toBe([
        'hasher' => ['type' => 'sha256'],
        'signer' => [
            'type' => 'hmac-sha256',
            'id' => 'signer-1',
            'secret' => 'key',
        ],
        'on_checksum_failure' => 'throw',
        'on_signature_failure' => 'throw',
        'on_missing_integrity' => 'ignore',
        'detached_attachments' => true,
    ]);
});

it('creates IntegrityConfig object', function () {
    $config = new IntegrityConfiguration(
        hasher: new Sha256HasherConfiguration(),
        signer: new HmacSha256SignerConfiguration(
            id: 'test',
            secret: new StringValueProvider('my_secret'),
        ),
    );

    $integrityConfig = $config->createIntegrityConfig();

    expect($integrityConfig)->toBeInstanceOf(\KDuma\SimpleDAL\Integrity\IntegrityConfig::class)
        ->and($integrityConfig->detachedAttachments)->toBeTrue();
});

it('throws on invalid failure mode', function () {
    IntegrityConfiguration::fromArray([
        'on_checksum_failure' => 'warn',
    ], '/base');
})->throws(InvalidArgumentException::class, 'Unknown failure mode: "warn"');

it('loads detached_attachments as false', function () {
    $config = IntegrityConfiguration::fromArray([
        'hasher' => ['type' => 'sha256'],
        'detached_attachments' => false,
    ], '/base');

    expect($config->detachedAttachments)->toBeFalse();

    $integrityConfig = $config->createIntegrityConfig();
    expect($integrityConfig->detachedAttachments)->toBeFalse();
});
