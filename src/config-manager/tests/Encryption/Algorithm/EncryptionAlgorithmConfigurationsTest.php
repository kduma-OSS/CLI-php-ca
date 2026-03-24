<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\AesAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\EncryptionAlgorithmConfigurationFactory;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\RsaAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\SealedBoxAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\SecretBoxAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\StringValueProvider;
use KDuma\SimpleDAL\Encryption\Contracts\EncryptionAlgorithmInterface;
use KDuma\SimpleDAL\Encryption\PhpSecLib\AesAlgorithm;
use KDuma\SimpleDAL\Encryption\PhpSecLib\RsaAlgorithm;
use KDuma\SimpleDAL\Encryption\Sodium\SealedBoxAlgorithm;
use KDuma\SimpleDAL\Encryption\Sodium\SecretBoxAlgorithm;
use phpseclib3\Crypt\RSA;

// ── SecretBox: createAlgorithm ──

test('SecretBox createAlgorithm returns SecretBoxAlgorithm', function () {
    $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

    $config = new SecretBoxAlgorithmConfiguration(
        id: 'sb-test',
        key: new StringValueProvider($key),
    );

    $algo = $config->createAlgorithm();

    expect($algo)->toBeInstanceOf(EncryptionAlgorithmInterface::class)
        ->and($algo)->toBeInstanceOf(SecretBoxAlgorithm::class)
        ->and($algo->id)->toBe('sb-test');
});

test('SecretBox can encrypt and decrypt', function () {
    $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

    $config = new SecretBoxAlgorithmConfiguration(
        id: 'sb-enc',
        key: new StringValueProvider($key),
    );

    $algo = $config->createAlgorithm();
    $plaintext = 'hello world';
    $ciphertext = $algo->encrypt($plaintext);
    $decrypted = $algo->decrypt($ciphertext);

    expect($decrypted)->toBe($plaintext);
});

// ── AES: fromArray, toArray, createAlgorithm ──

test('AES fromArray and toArray', function () {
    $key = random_bytes(32);
    $keyB64 = base64_encode($key);

    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'aes',
        'id' => 'aes-test',
        'key' => ['type' => 'base64', 'value' => $keyB64],
        'mode' => 'cbc',
    ], '/base');

    expect($algo)->toBeInstanceOf(AesAlgorithmConfiguration::class)
        ->and($algo->id)->toBe('aes-test')
        ->and($algo->mode)->toBe('cbc');

    $array = $algo->toArray();

    expect($array['type'])->toBe('aes')
        ->and($array['id'])->toBe('aes-test')
        ->and($array['mode'])->toBe('cbc');
});

test('AES createAlgorithm returns AesAlgorithm', function () {
    $key = random_bytes(32);

    $config = new AesAlgorithmConfiguration(
        id: 'aes-create',
        key: new StringValueProvider($key),
        mode: 'ctr',
    );

    $algo = $config->createAlgorithm();

    expect($algo)->toBeInstanceOf(EncryptionAlgorithmInterface::class)
        ->and($algo)->toBeInstanceOf(AesAlgorithm::class)
        ->and($algo->id)->toBe('aes-create');
});

test('AES can encrypt and decrypt', function () {
    $key = random_bytes(32);

    $config = new AesAlgorithmConfiguration(
        id: 'aes-enc',
        key: new StringValueProvider($key),
        mode: 'ctr',
    );

    $algo = $config->createAlgorithm();
    $plaintext = 'sensitive data';
    $ciphertext = $algo->encrypt($plaintext);
    $decrypted = $algo->decrypt($ciphertext);

    expect($decrypted)->toBe($plaintext);
});

test('AES default mode is ctr', function () {
    $key = random_bytes(32);

    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'aes',
        'id' => 'aes-default',
        'key' => ['type' => 'string', 'value' => $key],
    ], '/base');

    expect($algo)->toBeInstanceOf(AesAlgorithmConfiguration::class)
        ->and($algo->mode)->toBe('ctr');
});

// ── RSA Encryption: fromArray, toArray, createAlgorithm ──

test('RSA encryption fromArray and toArray', function () {
    $rsaKey = RSA::createKey(2048)->toString('PKCS8');

    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'rsa',
        'id' => 'rsa-enc-test',
        'key' => ['type' => 'string', 'value' => $rsaKey],
        'padding' => 'oaep',
        'hash' => 'sha256',
    ], '/base');

    expect($algo)->toBeInstanceOf(RsaAlgorithmConfiguration::class)
        ->and($algo->id)->toBe('rsa-enc-test')
        ->and($algo->padding)->toBe('oaep')
        ->and($algo->hash)->toBe('sha256');

    $array = $algo->toArray();

    expect($array['type'])->toBe('rsa')
        ->and($array['id'])->toBe('rsa-enc-test')
        ->and($array['padding'])->toBe('oaep')
        ->and($array['hash'])->toBe('sha256');
});

test('RSA encryption fromArray with all options', function () {
    $rsaKey = RSA::createKey(2048)->toString('PKCS8');

    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'rsa',
        'id' => 'rsa-full',
        'key' => ['type' => 'string', 'value' => $rsaKey],
        'padding' => 'oaep',
        'hash' => 'sha512',
        'mgf_hash' => 'sha256',
    ], '/base');

    expect($algo)->toBeInstanceOf(RsaAlgorithmConfiguration::class)
        ->and($algo->mgfHash)->toBe('sha256');

    $array = $algo->toArray();
    expect($array['mgf_hash'])->toBe('sha256');
});

test('RSA encryption createAlgorithm returns RsaAlgorithm', function () {
    $rsaKey = RSA::createKey(2048)->toString('PKCS8');

    $config = new RsaAlgorithmConfiguration(
        id: 'rsa-create',
        key: new StringValueProvider($rsaKey),
    );

    $algo = $config->createAlgorithm();

    expect($algo)->toBeInstanceOf(EncryptionAlgorithmInterface::class)
        ->and($algo)->toBeInstanceOf(RsaAlgorithm::class)
        ->and($algo->id)->toBe('rsa-create');
});

test('RSA encryption can encrypt and decrypt', function () {
    $rsaKey = RSA::createKey(2048);
    $privatePem = $rsaKey->toString('PKCS8');

    $config = new RsaAlgorithmConfiguration(
        id: 'rsa-enc-dec',
        key: new StringValueProvider($privatePem),
        padding: 'oaep',
        hash: 'sha256',
    );

    $algo = $config->createAlgorithm();
    $plaintext = 'rsa encrypted data';
    $ciphertext = $algo->encrypt($plaintext);
    $decrypted = $algo->decrypt($ciphertext);

    expect($decrypted)->toBe($plaintext);
});

test('RSA encryption with pkcs1 padding', function () {
    $rsaKey = RSA::createKey(2048);
    $privatePem = $rsaKey->toString('PKCS8');

    $config = new RsaAlgorithmConfiguration(
        id: 'rsa-pkcs1',
        key: new StringValueProvider($privatePem),
        padding: 'pkcs1',
    );

    $algo = $config->createAlgorithm();

    expect($algo)->toBeInstanceOf(RsaAlgorithm::class);

    $plaintext = 'pkcs1 data';
    $ciphertext = $algo->encrypt($plaintext);
    $decrypted = $algo->decrypt($ciphertext);

    expect($decrypted)->toBe($plaintext);
});

test('RSA encryption with mgfHash', function () {
    $rsaKey = RSA::createKey(2048);
    $privatePem = $rsaKey->toString('PKCS8');

    $config = new RsaAlgorithmConfiguration(
        id: 'rsa-mgf',
        key: new StringValueProvider($privatePem),
        padding: 'oaep',
        hash: 'sha256',
        mgfHash: 'sha256',
    );

    $algo = $config->createAlgorithm();

    $plaintext = 'mgf test';
    $ciphertext = $algo->encrypt($plaintext);
    $decrypted = $algo->decrypt($ciphertext);

    expect($decrypted)->toBe($plaintext);
});

test('RSA encryption throws on unknown padding', function () {
    $rsaKey = RSA::createKey(2048)->toString('PKCS8');

    $config = new RsaAlgorithmConfiguration(
        id: 'rsa-bad',
        key: new StringValueProvider($rsaKey),
        padding: 'unknown',
    );

    $config->createAlgorithm();
})->throws(InvalidArgumentException::class, 'Unknown RSA encryption padding');

// ── SealedBox: fromArray, toArray, createAlgorithm ──

test('SealedBox fromArray and toArray', function () {
    $keypair = sodium_crypto_box_keypair();
    $publicKey = sodium_crypto_box_publickey($keypair);
    $secretKey = sodium_crypto_box_secretkey($keypair);

    $pubB64 = base64_encode($publicKey);
    $secB64 = base64_encode($secretKey);

    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'sealed-box',
        'id' => 'sb-test',
        'public_key' => ['type' => 'base64', 'value' => $pubB64],
        'secret_key' => ['type' => 'base64', 'value' => $secB64],
    ], '/base');

    expect($algo)->toBeInstanceOf(SealedBoxAlgorithmConfiguration::class)
        ->and($algo->id)->toBe('sb-test');

    $array = $algo->toArray();

    expect($array['type'])->toBe('sealed-box')
        ->and($array['id'])->toBe('sb-test')
        ->and($array)->toHaveKey('public_key')
        ->and($array)->toHaveKey('secret_key');
});

test('SealedBox fromArray without secret_key', function () {
    $keypair = sodium_crypto_box_keypair();
    $publicKey = sodium_crypto_box_publickey($keypair);
    $pubB64 = base64_encode($publicKey);

    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'sealed-box',
        'id' => 'sb-encrypt-only',
        'public_key' => ['type' => 'base64', 'value' => $pubB64],
    ], '/base');

    expect($algo)->toBeInstanceOf(SealedBoxAlgorithmConfiguration::class)
        ->and($algo->secretKey)->toBeNull();

    $array = $algo->toArray();
    expect($array)->not->toHaveKey('secret_key');
});

test('SealedBox createAlgorithm returns SealedBoxAlgorithm', function () {
    $keypair = sodium_crypto_box_keypair();
    $publicKey = sodium_crypto_box_publickey($keypair);
    $secretKey = sodium_crypto_box_secretkey($keypair);

    $config = new SealedBoxAlgorithmConfiguration(
        id: 'sealed-create',
        publicKey: new StringValueProvider($publicKey),
        secretKey: new StringValueProvider($secretKey),
    );

    $algo = $config->createAlgorithm();

    expect($algo)->toBeInstanceOf(EncryptionAlgorithmInterface::class)
        ->and($algo)->toBeInstanceOf(SealedBoxAlgorithm::class)
        ->and($algo->id)->toBe('sealed-create');
});

test('SealedBox can encrypt and decrypt', function () {
    $keypair = sodium_crypto_box_keypair();
    $publicKey = sodium_crypto_box_publickey($keypair);
    $secretKey = sodium_crypto_box_secretkey($keypair);

    $config = new SealedBoxAlgorithmConfiguration(
        id: 'sealed-enc',
        publicKey: new StringValueProvider($publicKey),
        secretKey: new StringValueProvider($secretKey),
    );

    $algo = $config->createAlgorithm();
    $plaintext = 'sealed box data';
    $ciphertext = $algo->encrypt($plaintext);
    $decrypted = $algo->decrypt($ciphertext);

    expect($decrypted)->toBe($plaintext);
});

// ── SecretBox: fromArray round-trip with string shorthand ──

test('SecretBox fromArray with string shorthand', function () {
    $key = str_repeat('x', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

    $factory = new EncryptionAlgorithmConfigurationFactory;
    $algo = $factory->fromArray([
        'type' => 'secret-box',
        'id' => 'sb-shorthand',
        'key' => $key,
    ], '/base');

    expect($algo)->toBeInstanceOf(SecretBoxAlgorithmConfiguration::class)
        ->and($algo->id)->toBe('sb-shorthand');

    $array = $algo->toArray();

    expect($array)->toBe([
        'type' => 'secret-box',
        'id' => 'sb-shorthand',
        'key' => $key,
    ]);
});
