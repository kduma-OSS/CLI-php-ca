<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\Integrity\Signer\DsaSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\EcSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\Ed25519SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha1SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha256SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha512SignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\RsaSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\SignerConfigurationFactory;
use KDuma\PhpCA\ConfigManager\ValueProvider\StringValueProvider;
use KDuma\SimpleDAL\Integrity\Contracts\SigningAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\Hash\Signer\HmacSha1SigningAlgorithm;
use KDuma\SimpleDAL\Integrity\Hash\Signer\HmacSha256SigningAlgorithm;
use KDuma\SimpleDAL\Integrity\Hash\Signer\HmacSha512SigningAlgorithm;
use KDuma\SimpleDAL\Integrity\PhpSecLib\DsaSigningAlgorithm;
use KDuma\SimpleDAL\Integrity\PhpSecLib\EcSigningAlgorithm;
use KDuma\SimpleDAL\Integrity\PhpSecLib\RsaSigningAlgorithm;
use KDuma\SimpleDAL\Integrity\Sodium\Ed25519SigningAlgorithm;
use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\RSA;

// ── HMAC signers: createSigner ──

test('HmacSha1 createSigner returns HmacSha1SigningAlgorithm', function () {
    $config = new HmacSha1SignerConfiguration(
        id: 'hmac1',
        secret: new StringValueProvider('my-secret'),
    );

    $signer = $config->createSigner();

    expect($signer)->toBeInstanceOf(SigningAlgorithmInterface::class)
        ->and($signer)->toBeInstanceOf(HmacSha1SigningAlgorithm::class)
        ->and($signer->id)->toBe('hmac1');
});

test('HmacSha256 createSigner returns HmacSha256SigningAlgorithm', function () {
    $config = new HmacSha256SignerConfiguration(
        id: 'hmac256',
        secret: new StringValueProvider('my-secret'),
    );

    $signer = $config->createSigner();

    expect($signer)->toBeInstanceOf(HmacSha256SigningAlgorithm::class)
        ->and($signer->id)->toBe('hmac256');
});

test('HmacSha512 createSigner returns HmacSha512SigningAlgorithm', function () {
    $config = new HmacSha512SignerConfiguration(
        id: 'hmac512',
        secret: new StringValueProvider('my-secret'),
    );

    $signer = $config->createSigner();

    expect($signer)->toBeInstanceOf(HmacSha512SigningAlgorithm::class)
        ->and($signer->id)->toBe('hmac512');
});

test('HMAC signer can sign and verify', function (string $configClass) {
    $config = new $configClass(
        id: 'test',
        secret: new StringValueProvider('test-secret-key'),
    );

    $signer = $config->createSigner();
    $signature = $signer->sign('hello world');

    expect($signer->verify('hello world', $signature))->toBeTrue()
        ->and($signer->verify('tampered', $signature))->toBeFalse();
})->with([
    'hmac-sha1' => [HmacSha1SignerConfiguration::class],
    'hmac-sha256' => [HmacSha256SignerConfiguration::class],
    'hmac-sha512' => [HmacSha512SignerConfiguration::class],
]);

// ── Ed25519 signer ──

test('Ed25519 createSigner returns Ed25519SigningAlgorithm', function () {
    $keypair = sodium_crypto_sign_keypair();
    $publicKey = sodium_crypto_sign_publickey($keypair);
    $secretKey = sodium_crypto_sign_secretkey($keypair);

    $config = new Ed25519SignerConfiguration(
        id: 'ed-signer',
        publicKey: new StringValueProvider($publicKey),
        secretKey: new StringValueProvider($secretKey),
    );

    $signer = $config->createSigner();

    expect($signer)->toBeInstanceOf(Ed25519SigningAlgorithm::class)
        ->and($signer->id)->toBe('ed-signer');
});

test('Ed25519 signer can sign and verify', function () {
    $keypair = sodium_crypto_sign_keypair();
    $publicKey = sodium_crypto_sign_publickey($keypair);
    $secretKey = sodium_crypto_sign_secretkey($keypair);

    $config = new Ed25519SignerConfiguration(
        id: 'ed-test',
        publicKey: new StringValueProvider($publicKey),
        secretKey: new StringValueProvider($secretKey),
    );

    $signer = $config->createSigner();
    $signature = $signer->sign('test message');

    expect($signer->verify('test message', $signature))->toBeTrue()
        ->and($signer->verify('wrong message', $signature))->toBeFalse();
});

test('Ed25519 fromArray and toArray', function () {
    $keypair = sodium_crypto_sign_keypair();
    $publicKey = sodium_crypto_sign_publickey($keypair);
    $secretKey = sodium_crypto_sign_secretkey($keypair);

    $pubB64 = base64_encode($publicKey);
    $secB64 = base64_encode($secretKey);

    $factory = new SignerConfigurationFactory;
    $signer = $factory->fromArray([
        'type' => 'ed25519',
        'id' => 'ed-round-trip',
        'public_key' => ['type' => 'base64', 'value' => $pubB64],
        'secret_key' => ['type' => 'base64', 'value' => $secB64],
    ], '/base');

    expect($signer)->toBeInstanceOf(Ed25519SignerConfiguration::class)
        ->and($signer->id)->toBe('ed-round-trip');

    $array = $signer->toArray();

    expect($array['type'])->toBe('ed25519')
        ->and($array['id'])->toBe('ed-round-trip')
        ->and($array)->toHaveKey('public_key')
        ->and($array)->toHaveKey('secret_key');
});

test('Ed25519 fromArray without secret_key', function () {
    $keypair = sodium_crypto_sign_keypair();
    $publicKey = sodium_crypto_sign_publickey($keypair);
    $pubB64 = base64_encode($publicKey);

    $factory = new SignerConfigurationFactory;
    $signer = $factory->fromArray([
        'type' => 'ed25519',
        'id' => 'ed-verify-only',
        'public_key' => ['type' => 'base64', 'value' => $pubB64],
    ], '/base');

    expect($signer)->toBeInstanceOf(Ed25519SignerConfiguration::class)
        ->and($signer->secretKey)->toBeNull();

    $array = $signer->toArray();
    expect($array)->not->toHaveKey('secret_key');
});

// ── RSA signer ──

test('RSA signer fromArray and toArray', function () {
    $rsaKey = RSA::createKey(2048)->toString('PKCS8');

    $factory = new SignerConfigurationFactory;
    $signer = $factory->fromArray([
        'type' => 'rsa',
        'id' => 'rsa-signer',
        'key' => ['type' => 'string', 'value' => $rsaKey],
        'padding' => 'pss',
        'hash' => 'sha256',
    ], '/base');

    expect($signer)->toBeInstanceOf(RsaSignerConfiguration::class)
        ->and($signer->id)->toBe('rsa-signer')
        ->and($signer->padding)->toBe('pss')
        ->and($signer->hash)->toBe('sha256');

    $array = $signer->toArray();

    expect($array['type'])->toBe('rsa')
        ->and($array['id'])->toBe('rsa-signer')
        ->and($array['padding'])->toBe('pss')
        ->and($array['hash'])->toBe('sha256');
});

test('RSA signer createSigner returns RsaSigningAlgorithm', function () {
    $rsaKey = RSA::createKey(2048)->toString('PKCS8');

    $config = new RsaSignerConfiguration(
        id: 'rsa-test',
        key: new StringValueProvider($rsaKey),
    );

    $signer = $config->createSigner();

    expect($signer)->toBeInstanceOf(RsaSigningAlgorithm::class)
        ->and($signer->id)->toBe('rsa-test');
});

test('RSA signer can sign and verify', function () {
    $rsaKey = RSA::createKey(2048);
    $privatePem = $rsaKey->toString('PKCS8');

    $config = new RsaSignerConfiguration(
        id: 'rsa-sign-test',
        key: new StringValueProvider($privatePem),
        padding: 'pkcs1',
        hash: 'sha256',
    );

    $signer = $config->createSigner();
    $signature = $signer->sign('test message');

    expect($signer->verify('test message', $signature))->toBeTrue();
});

test('RSA signer with pss padding', function () {
    $rsaKey = RSA::createKey(2048);
    $privatePem = $rsaKey->toString('PKCS8');

    $config = new RsaSignerConfiguration(
        id: 'rsa-pss',
        key: new StringValueProvider($privatePem),
        padding: 'pss',
        hash: 'sha256',
        mgfHash: 'sha256',
        saltLength: 32,
    );

    $signer = $config->createSigner();

    expect($signer)->toBeInstanceOf(RsaSigningAlgorithm::class);

    $signature = $signer->sign('pss test');
    expect($signer->verify('pss test', $signature))->toBeTrue();
});

test('RSA signer fromArray with all options', function () {
    $rsaKey = RSA::createKey(2048)->toString('PKCS8');

    $factory = new SignerConfigurationFactory;
    $signer = $factory->fromArray([
        'type' => 'rsa',
        'id' => 'rsa-full',
        'key' => ['type' => 'string', 'value' => $rsaKey],
        'padding' => 'pss',
        'hash' => 'sha512',
        'mgf_hash' => 'sha256',
        'salt_length' => 64,
    ], '/base');

    expect($signer)->toBeInstanceOf(RsaSignerConfiguration::class)
        ->and($signer->mgfHash)->toBe('sha256')
        ->and($signer->saltLength)->toBe(64);

    $array = $signer->toArray();
    expect($array['mgf_hash'])->toBe('sha256')
        ->and($array['salt_length'])->toBe(64);
});

test('RSA signer throws on unknown padding', function () {
    $rsaKey = RSA::createKey(2048)->toString('PKCS8');

    $config = new RsaSignerConfiguration(
        id: 'rsa-bad',
        key: new StringValueProvider($rsaKey),
        padding: 'unknown',
    );

    $config->createSigner();
})->throws(InvalidArgumentException::class, 'Unknown RSA signing padding');

// ── EC signer ──

test('EC signer fromArray and toArray', function () {
    $ecKey = EC::createKey('secp256r1')->toString('PKCS8');

    $factory = new SignerConfigurationFactory;
    $signer = $factory->fromArray([
        'type' => 'ec',
        'id' => 'ec-signer',
        'key' => ['type' => 'string', 'value' => $ecKey],
        'hash' => 'sha256',
    ], '/base');

    expect($signer)->toBeInstanceOf(EcSignerConfiguration::class)
        ->and($signer->id)->toBe('ec-signer')
        ->and($signer->hash)->toBe('sha256');

    $array = $signer->toArray();

    expect($array['type'])->toBe('ec')
        ->and($array['id'])->toBe('ec-signer')
        ->and($array['hash'])->toBe('sha256');
});

test('EC signer createSigner returns EcSigningAlgorithm', function () {
    $ecKey = EC::createKey('secp256r1')->toString('PKCS8');

    $config = new EcSignerConfiguration(
        id: 'ec-test',
        key: new StringValueProvider($ecKey),
    );

    $signer = $config->createSigner();

    expect($signer)->toBeInstanceOf(EcSigningAlgorithm::class)
        ->and($signer->id)->toBe('ec-test');
});

test('EC signer can sign and verify', function () {
    $ecKey = EC::createKey('secp256r1')->toString('PKCS8');

    $config = new EcSignerConfiguration(
        id: 'ec-sign-test',
        key: new StringValueProvider($ecKey),
        hash: 'sha256',
    );

    $signer = $config->createSigner();
    $signature = $signer->sign('ec test message');

    expect($signer->verify('ec test message', $signature))->toBeTrue();
});

// ── DSA signer ──

test('DSA signer fromArray and toArray', function () {
    $dsaKey = DSA::createKey(2048, 256)->toString('PKCS8');

    $factory = new SignerConfigurationFactory;
    $signer = $factory->fromArray([
        'type' => 'dsa',
        'id' => 'dsa-signer',
        'key' => ['type' => 'string', 'value' => $dsaKey],
        'hash' => 'sha256',
    ], '/base');

    expect($signer)->toBeInstanceOf(DsaSignerConfiguration::class)
        ->and($signer->id)->toBe('dsa-signer')
        ->and($signer->hash)->toBe('sha256');

    $array = $signer->toArray();

    expect($array['type'])->toBe('dsa')
        ->and($array['id'])->toBe('dsa-signer')
        ->and($array['hash'])->toBe('sha256');
});

test('DSA signer createSigner returns DsaSigningAlgorithm', function () {
    $dsaKey = DSA::createKey(2048, 256)->toString('PKCS8');

    $config = new DsaSignerConfiguration(
        id: 'dsa-test',
        key: new StringValueProvider($dsaKey),
    );

    $signer = $config->createSigner();

    expect($signer)->toBeInstanceOf(DsaSigningAlgorithm::class)
        ->and($signer->id)->toBe('dsa-test');
});

test('DSA signer can sign and verify', function () {
    $dsaKey = DSA::createKey(2048, 256)->toString('PKCS8');

    $config = new DsaSignerConfiguration(
        id: 'dsa-sign-test',
        key: new StringValueProvider($dsaKey),
        hash: 'sha256',
    );

    $signer = $config->createSigner();
    $signature = $signer->sign('dsa test message');

    expect($signer->verify('dsa test message', $signature))->toBeTrue();
});

// ── HMAC fromArray/toArray for sha1 and sha512 ──

test('HmacSha1 fromArray and toArray', function () {
    $factory = new SignerConfigurationFactory;
    $signer = $factory->fromArray([
        'type' => 'hmac-sha1',
        'id' => 'hmac1-test',
        'secret' => ['type' => 'string', 'value' => 'my-secret'],
    ], '/base');

    expect($signer)->toBeInstanceOf(HmacSha1SignerConfiguration::class)
        ->and($signer->id)->toBe('hmac1-test');

    $array = $signer->toArray();

    expect($array['type'])->toBe('hmac-sha1')
        ->and($array['id'])->toBe('hmac1-test')
        ->and($array['secret'])->toBe('my-secret');
});

test('HmacSha512 fromArray and toArray', function () {
    $factory = new SignerConfigurationFactory;
    $signer = $factory->fromArray([
        'type' => 'hmac-sha512',
        'id' => 'hmac512-test',
        'secret' => ['type' => 'string', 'value' => 'my-512-secret'],
    ], '/base');

    expect($signer)->toBeInstanceOf(HmacSha512SignerConfiguration::class)
        ->and($signer->id)->toBe('hmac512-test');

    $array = $signer->toArray();

    expect($array['type'])->toBe('hmac-sha512')
        ->and($array['id'])->toBe('hmac512-test')
        ->and($array['secret'])->toBe('my-512-secret');
});
