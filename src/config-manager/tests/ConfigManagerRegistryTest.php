<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Adapter\DirectoryAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\MySqlAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\SqliteAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\ZipAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\ConfigManagerRegistry;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\AesAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\RsaAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\SealedBoxAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\SecretBoxAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Blake2bHasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Crc32HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Md5HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha1HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha3_256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha512HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\DsaSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\EcSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\Ed25519SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha1SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha256SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha512SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\RsaSignerConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\Base64ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\EnvValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ExplodeValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\FileValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\FirstValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\JsonValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\StringValueProvider;

beforeEach(function () {
    ConfigManagerRegistry::reset();
});

afterAll(function () {
    // Restore defaults for other tests
    ConfigManagerRegistry::reset();
    ConfigManagerRegistry::registerDefaults();
});

test('registerDefaults populates adapter types', function () {
    ConfigManagerRegistry::registerDefaults();

    $adapters = ConfigManagerRegistry::getAdapterTypes();

    expect($adapters)->toBeArray()
        ->toHaveKeys(['directory', 'sqlite', 'zip', 'mysql'])
        ->and($adapters['directory'])->toBe(DirectoryAdapterConfiguration::class)
        ->and($adapters['sqlite'])->toBe(SqliteAdapterConfiguration::class)
        ->and($adapters['zip'])->toBe(ZipAdapterConfiguration::class)
        ->and($adapters['mysql'])->toBe(MySqlAdapterConfiguration::class);
});

test('registerDefaults populates value provider types', function () {
    ConfigManagerRegistry::registerDefaults();

    $providers = ConfigManagerRegistry::getValueProviderTypes();

    expect($providers)->toBeArray()
        ->toHaveKeys(['string', 'base64', 'env', 'file', 'first', 'explode', 'json'])
        ->and($providers['string'])->toBe(StringValueProvider::class)
        ->and($providers['base64'])->toBe(Base64ValueProvider::class)
        ->and($providers['env'])->toBe(EnvValueProvider::class)
        ->and($providers['file'])->toBe(FileValueProvider::class)
        ->and($providers['first'])->toBe(FirstValueProvider::class)
        ->and($providers['explode'])->toBe(ExplodeValueProvider::class)
        ->and($providers['json'])->toBe(JsonValueProvider::class);
});

test('registerDefaults populates hasher types', function () {
    ConfigManagerRegistry::registerDefaults();

    $hashers = ConfigManagerRegistry::getHasherTypes();

    expect($hashers)->toBeArray()
        ->toHaveKeys(['crc32', 'md5', 'sha1', 'sha256', 'sha3-256', 'sha512', 'blake2b'])
        ->and($hashers['crc32'])->toBe(Crc32HasherConfiguration::class)
        ->and($hashers['md5'])->toBe(Md5HasherConfiguration::class)
        ->and($hashers['sha1'])->toBe(Sha1HasherConfiguration::class)
        ->and($hashers['sha256'])->toBe(Sha256HasherConfiguration::class)
        ->and($hashers['sha3-256'])->toBe(Sha3_256HasherConfiguration::class)
        ->and($hashers['sha512'])->toBe(Sha512HasherConfiguration::class)
        ->and($hashers['blake2b'])->toBe(Blake2bHasherConfiguration::class);
});

test('registerDefaults populates signer types', function () {
    ConfigManagerRegistry::registerDefaults();

    $signers = ConfigManagerRegistry::getSignerTypes();

    expect($signers)->toBeArray()
        ->toHaveKeys(['hmac-sha1', 'hmac-sha256', 'hmac-sha512', 'ed25519', 'rsa', 'ec', 'dsa'])
        ->and($signers['hmac-sha1'])->toBe(HmacSha1SignerConfiguration::class)
        ->and($signers['hmac-sha256'])->toBe(HmacSha256SignerConfiguration::class)
        ->and($signers['hmac-sha512'])->toBe(HmacSha512SignerConfiguration::class)
        ->and($signers['ed25519'])->toBe(Ed25519SignerConfiguration::class)
        ->and($signers['rsa'])->toBe(RsaSignerConfiguration::class)
        ->and($signers['ec'])->toBe(EcSignerConfiguration::class)
        ->and($signers['dsa'])->toBe(DsaSignerConfiguration::class);
});

test('registerDefaults populates encryption algorithm types', function () {
    ConfigManagerRegistry::registerDefaults();

    $algos = ConfigManagerRegistry::getEncryptionAlgorithmTypes();

    expect($algos)->toBeArray()
        ->toHaveKeys(['secret-box', 'sealed-box', 'aes', 'rsa'])
        ->and($algos['secret-box'])->toBe(SecretBoxAlgorithmConfiguration::class)
        ->and($algos['sealed-box'])->toBe(SealedBoxAlgorithmConfiguration::class)
        ->and($algos['aes'])->toBe(AesAlgorithmConfiguration::class)
        ->and($algos['rsa'])->toBe(RsaAlgorithmConfiguration::class);
});

test('registerDefaults is idempotent', function () {
    ConfigManagerRegistry::registerDefaults();
    ConfigManagerRegistry::registerDefaults();

    $hashers = ConfigManagerRegistry::getHasherTypes();
    expect($hashers)->toHaveCount(7);
});

test('reset clears all registries', function () {
    ConfigManagerRegistry::registerDefaults();

    expect(ConfigManagerRegistry::getAdapterTypes())->not->toBeEmpty();
    expect(ConfigManagerRegistry::getValueProviderTypes())->not->toBeEmpty();
    expect(ConfigManagerRegistry::getHasherTypes())->not->toBeEmpty();
    expect(ConfigManagerRegistry::getSignerTypes())->not->toBeEmpty();
    expect(ConfigManagerRegistry::getEncryptionAlgorithmTypes())->not->toBeEmpty();

    ConfigManagerRegistry::reset();

    expect(ConfigManagerRegistry::getAdapterTypes())->toBeEmpty()
        ->and(ConfigManagerRegistry::getValueProviderTypes())->toBeEmpty()
        ->and(ConfigManagerRegistry::getHasherTypes())->toBeEmpty()
        ->and(ConfigManagerRegistry::getSignerTypes())->toBeEmpty()
        ->and(ConfigManagerRegistry::getEncryptionAlgorithmTypes())->toBeEmpty();
});

test('reset allows registerDefaults to run again', function () {
    ConfigManagerRegistry::registerDefaults();
    ConfigManagerRegistry::reset();

    expect(ConfigManagerRegistry::getHasherTypes())->toBeEmpty();

    ConfigManagerRegistry::registerDefaults();

    expect(ConfigManagerRegistry::getHasherTypes())->toHaveCount(7);
});

test('register adds a single adapter type', function () {
    ConfigManagerRegistry::register(DirectoryAdapterConfiguration::class);

    expect(ConfigManagerRegistry::getAdapterTypes())->toHaveKey('directory')
        ->and(ConfigManagerRegistry::getAdapterTypes()['directory'])->toBe(DirectoryAdapterConfiguration::class);
});

test('register adds a single hasher type', function () {
    ConfigManagerRegistry::register(Sha256HasherConfiguration::class);

    expect(ConfigManagerRegistry::getHasherTypes())->toHaveKey('sha256')
        ->and(ConfigManagerRegistry::getHasherTypes()['sha256'])->toBe(Sha256HasherConfiguration::class);
});

test('register adds a single signer type', function () {
    ConfigManagerRegistry::register(HmacSha256SignerConfiguration::class);

    expect(ConfigManagerRegistry::getSignerTypes())->toHaveKey('hmac-sha256')
        ->and(ConfigManagerRegistry::getSignerTypes()['hmac-sha256'])->toBe(HmacSha256SignerConfiguration::class);
});

test('register adds a single encryption algorithm type', function () {
    ConfigManagerRegistry::register(SecretBoxAlgorithmConfiguration::class);

    expect(ConfigManagerRegistry::getEncryptionAlgorithmTypes())->toHaveKey('secret-box')
        ->and(ConfigManagerRegistry::getEncryptionAlgorithmTypes()['secret-box'])->toBe(SecretBoxAlgorithmConfiguration::class);
});

test('register adds a single value provider type', function () {
    ConfigManagerRegistry::register(StringValueProvider::class);

    expect(ConfigManagerRegistry::getValueProviderTypes())->toHaveKey('string')
        ->and(ConfigManagerRegistry::getValueProviderTypes()['string'])->toBe(StringValueProvider::class);
});

test('register throws on unrecognized class', function () {
    ConfigManagerRegistry::register(stdClass::class);
})->throws(LogicException::class, 'does not have a recognized configuration attribute');

test('getter methods return empty arrays initially', function () {
    expect(ConfigManagerRegistry::getAdapterTypes())->toBe([])
        ->and(ConfigManagerRegistry::getValueProviderTypes())->toBe([])
        ->and(ConfigManagerRegistry::getHasherTypes())->toBe([])
        ->and(ConfigManagerRegistry::getSignerTypes())->toBe([])
        ->and(ConfigManagerRegistry::getEncryptionAlgorithmTypes())->toBe([]);
});
