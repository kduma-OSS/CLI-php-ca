<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager;

use KDuma\PhpCA\ConfigManager\Adapter\Attributes\AdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\DirectoryAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\MySqlAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\SqliteAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Adapter\ZipAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\AesAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\Attributes\EncryptionAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\RsaAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\SealedBoxAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\SecretBoxAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Attributes\HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Blake2bHasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Crc32HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Md5HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha1HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha3_256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha512HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\Attributes\SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\DsaSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\EcSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\Ed25519SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha1SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha256SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha512SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\RsaSignerConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;
use KDuma\PhpCA\ConfigManager\ValueProvider\Base64ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\EnvValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ExplodeValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\FileValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\FirstValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\JsonValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\StringValueProvider;
use LogicException;
use Spatie\Attributes\Attributes;

class ConfigManagerRegistry
{
    /** @var array<string, class-string> */
    private static array $adapterTypes = [];

    /** @var array<string, class-string> */
    private static array $valueProviderTypes = [];

    /** @var array<string, class-string> */
    private static array $hasherTypes = [];

    /** @var array<string, class-string> */
    private static array $signerTypes = [];

    /** @var array<string, class-string> */
    private static array $encryptionAlgorithmTypes = [];

    private static bool $defaultsRegistered = false;

    public static function register(string $class): void
    {
        $attr = Attributes::get($class, AdapterConfiguration::class);
        if ($attr !== null) {
            self::$adapterTypes[$attr->type] = $class;

            return;
        }

        $attr = Attributes::get($class, ValueProviderType::class);
        if ($attr !== null) {
            self::$valueProviderTypes[$attr->type] = $class;

            return;
        }

        $attr = Attributes::get($class, HasherConfiguration::class);
        if ($attr !== null) {
            self::$hasherTypes[$attr->type] = $class;

            return;
        }

        $attr = Attributes::get($class, SignerConfiguration::class);
        if ($attr !== null) {
            self::$signerTypes[$attr->type] = $class;

            return;
        }

        $attr = Attributes::get($class, EncryptionAlgorithmConfiguration::class);
        if ($attr !== null) {
            self::$encryptionAlgorithmTypes[$attr->type] = $class;

            return;
        }

        throw new LogicException("Class {$class} does not have a recognized configuration attribute.");
    }

    public static function registerDefaults(): void
    {
        if (self::$defaultsRegistered) {
            return;
        }

        self::$defaultsRegistered = true;

        // Adapters
        self::register(DirectoryAdapterConfiguration::class);
        self::register(SqliteAdapterConfiguration::class);
        self::register(ZipAdapterConfiguration::class);
        self::register(MySqlAdapterConfiguration::class);

        // Value Providers
        self::register(StringValueProvider::class);
        self::register(Base64ValueProvider::class);
        self::register(EnvValueProvider::class);
        self::register(FileValueProvider::class);
        self::register(FirstValueProvider::class);
        self::register(ExplodeValueProvider::class);
        self::register(JsonValueProvider::class);

        // Hashers
        self::register(Crc32HasherConfiguration::class);
        self::register(Md5HasherConfiguration::class);
        self::register(Sha1HasherConfiguration::class);
        self::register(Sha256HasherConfiguration::class);
        self::register(Sha3_256HasherConfiguration::class);
        self::register(Sha512HasherConfiguration::class);
        self::register(Blake2bHasherConfiguration::class);

        // Signers
        self::register(HmacSha1SignerConfiguration::class);
        self::register(HmacSha256SignerConfiguration::class);
        self::register(HmacSha512SignerConfiguration::class);
        self::register(Ed25519SignerConfiguration::class);
        self::register(RsaSignerConfiguration::class);
        self::register(EcSignerConfiguration::class);
        self::register(DsaSignerConfiguration::class);

        // Encryption Algorithms
        self::register(SecretBoxAlgorithmConfiguration::class);
        self::register(SealedBoxAlgorithmConfiguration::class);
        self::register(AesAlgorithmConfiguration::class);
        self::register(RsaAlgorithmConfiguration::class);
    }

    /** @return array<string, class-string> */
    public static function getAdapterTypes(): array
    {
        return self::$adapterTypes;
    }

    /** @return array<string, class-string> */
    public static function getValueProviderTypes(): array
    {
        return self::$valueProviderTypes;
    }

    /** @return array<string, class-string> */
    public static function getHasherTypes(): array
    {
        return self::$hasherTypes;
    }

    /** @return array<string, class-string> */
    public static function getSignerTypes(): array
    {
        return self::$signerTypes;
    }

    /** @return array<string, class-string> */
    public static function getEncryptionAlgorithmTypes(): array
    {
        return self::$encryptionAlgorithmTypes;
    }

    public static function reset(): void
    {
        self::$adapterTypes = [];
        self::$valueProviderTypes = [];
        self::$hasherTypes = [];
        self::$signerTypes = [];
        self::$encryptionAlgorithmTypes = [];
        self::$defaultsRegistered = false;
    }
}
