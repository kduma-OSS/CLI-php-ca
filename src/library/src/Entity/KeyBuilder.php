<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\Helpers\FingerprintHelper;
use KDuma\PhpCA\Record\KeyType\BaseKeyType;
use KDuma\PhpCA\Record\KeyType\DSAKeyType;
use KDuma\PhpCA\Record\KeyType\ECDSAKeyType;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\DsaParameterSize;
use KDuma\PhpCA\Record\KeyType\Enum\EcCurve;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;
use KDuma\PhpCA\Record\KeyType\RSAKeyType;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

class KeyBuilder
{
    private PrivateKey|PublicKey $key;
    private BaseKeyType $type;
    private bool $hasPrivateKey;

    private function __construct() {}

    public static function fromExisting(string|PrivateKey|PublicKey $key): static
    {
        if (is_string($key)) {
            $key = PublicKeyLoader::load($key);
        }

        $builder = new static();
        $builder->key = self::normalizeRsaPadding($key);
        $builder->hasPrivateKey = $key instanceof PrivateKey;
        $builder->type = self::detectKeyType($builder->key);

        return $builder;
    }

    public static function fresh(BaseKeyType $type): static
    {
        $builder = new static();
        $builder->type = $type;
        $builder->hasPrivateKey = true;
        $builder->key = self::generateKey($type);

        return $builder;
    }

    public function make(): KeyEntity
    {
        $publicKey = $this->key instanceof PrivateKey
            ? $this->key->getPublicKey()
            : $this->key;

        $entity = new KeyEntity();
        $entity->type = $this->type;
        $entity->fingerprint = FingerprintHelper::compute($publicKey);
        $entity->hasPrivateKey = $this->hasPrivateKey;
        $entity->publicKey = $publicKey->toString('PKCS8');

        if ($this->hasPrivateKey) {
            assert($this->key instanceof PrivateKey);
            $privateKey = $this->key->withPassword();
            $entity->privateKey = $privateKey->toString('PKCS8');
        }

        return $entity;
    }

    private static function detectKeyType(PrivateKey|PublicKey $key): BaseKeyType
    {
        if ($key instanceof RSA\PrivateKey || $key instanceof RSA\PublicKey) {
            return new RSAKeyType(size: $key->getLength());
        }

        if ($key instanceof DSA\PrivateKey || $key instanceof DSA\PublicKey) {
            $length = $key->getLength();
            return new DSAKeyType(
                parameters: DsaParameterSize::fromParameters($length['L'], $length['N']),
            );
        }

        if ($key instanceof EC\PrivateKey || $key instanceof EC\PublicKey) {
            $curve = $key->getCurve();

            return match (true) {
                $curve === 'Ed25519', $curve === 'Ed448' => new EdDSAKeyType(
                    curve: EdDSACurve::from($curve),
                ),
                default => new ECDSAKeyType(
                    curve: EcCurve::from($curve),
                ),
            };
        }

        throw new \InvalidArgumentException('Unsupported key type: ' . get_class($key));
    }

    /**
     * Normalize RSA keys to use rsaEncryption OID (PKCS1 v1.5) instead of rsassaPss.
     */
    private static function normalizeRsaPadding(PrivateKey|PublicKey $key): PrivateKey|PublicKey
    {
        if ($key instanceof RSA\PrivateKey) {
            return $key->withPadding(RSA::SIGNATURE_PKCS1 | RSA::ENCRYPTION_PKCS1);
        }

        if ($key instanceof RSA\PublicKey) {
            return $key->withPadding(RSA::SIGNATURE_PKCS1 | RSA::ENCRYPTION_PKCS1);
        }

        return $key;
    }

    private static function generateKey(BaseKeyType $type): PrivateKey
    {
        return match (true) {
            $type instanceof RSAKeyType => RSA::createKey($type->size)
                ->withPadding(RSA::SIGNATURE_PKCS1 | RSA::ENCRYPTION_PKCS1),
            $type instanceof DSAKeyType => DSA::createKey($type->parameters->L(), $type->parameters->N()),
            $type instanceof ECDSAKeyType => EC::createKey($type->curve->value),
            $type instanceof EdDSAKeyType => EC::createKey($type->curve->value),
            default => throw new \InvalidArgumentException('Unsupported key type: ' . get_class($type)),
        };
    }
}
