<?php

declare(strict_types=1);

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Entity\KeyBuilder;
use KDuma\PhpCA\Entity\KeyEntity;
use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;
use KDuma\PhpCA\Record\KeyType\RSAKeyType;
use KDuma\SimpleDAL\Adapter\Flysystem\FlysystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

function createTempCaForKeyTests(): CertificationAuthority
{
    $tempDir = sys_get_temp_dir() . '/php-ca-test-' . uniqid();
    mkdir($tempDir, 0777, true);
    $filesystem = new Filesystem(new LocalFilesystemAdapter($tempDir));
    $adapter = new FlysystemAdapter($filesystem);
    ExtensionRegistry::registerDefaults();

    return new CertificationAuthority($adapter);
}

test('create EdDSA key, save, find, and verify properties', function () {
    $ca = createTempCaForKeyTests();

    $keyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);
    $keyEntity = KeyBuilder::fresh($keyType)->make();

    expect($keyEntity)->toBeInstanceOf(KeyEntity::class)
        ->and($keyEntity->type)->toBeInstanceOf(EdDSAKeyType::class)
        ->and($keyEntity->type->curve)->toBe(EdDSACurve::Ed25519)
        ->and($keyEntity->hasPrivateKey)->toBeTrue()
        ->and($keyEntity->fingerprint)->toBeString()->not->toBeEmpty()
        ->and($keyEntity->publicKey)->toBeString()->toContain('PUBLIC KEY')
        ->and($keyEntity->privateKey)->toBeString()->toContain('PRIVATE KEY');

    $ca->keys->save($keyEntity);

    expect($keyEntity->persisted)->toBeTrue()
        ->and($keyEntity->id)->not->toBeNull();

    $found = $ca->keys->find($keyEntity->id);

    expect($found)->toBeInstanceOf(KeyEntity::class)
        ->and($found->id)->toBe($keyEntity->id)
        ->and($found->fingerprint)->toBe($keyEntity->fingerprint)
        ->and($found->hasPrivateKey)->toBeTrue()
        ->and($found->type)->toBeInstanceOf(EdDSAKeyType::class)
        ->and($found->type->curve)->toBe(EdDSACurve::Ed25519)
        ->and($found->publicKey)->toContain('PUBLIC KEY')
        ->and($found->privateKey)->toContain('PRIVATE KEY');
});

test('create RSA key, save, find, and verify properties', function () {
    $ca = createTempCaForKeyTests();

    $keyType = new RSAKeyType(size: 2048);
    $keyEntity = KeyBuilder::fresh($keyType)->make();

    expect($keyEntity)->toBeInstanceOf(KeyEntity::class)
        ->and($keyEntity->type)->toBeInstanceOf(RSAKeyType::class)
        ->and($keyEntity->type->size)->toBe(2048)
        ->and($keyEntity->hasPrivateKey)->toBeTrue();

    $ca->keys->save($keyEntity);

    $found = $ca->keys->find($keyEntity->id);

    expect($found->type)->toBeInstanceOf(RSAKeyType::class)
        ->and($found->type->size)->toBe(2048)
        ->and($found->hasPrivateKey)->toBeTrue()
        ->and($found->publicKey)->toContain('PUBLIC KEY')
        ->and($found->privateKey)->toContain('PRIVATE KEY');
});

test('create key with explicit ID and verify ID is used', function () {
    $ca = createTempCaForKeyTests();

    $keyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);
    $keyEntity = KeyBuilder::fresh($keyType)->make();
    $keyEntity->id = 'my-custom-key-id';

    $ca->keys->save($keyEntity);

    expect($keyEntity->id)->toBe('my-custom-key-id');

    $found = $ca->keys->find('my-custom-key-id');

    expect($found->id)->toBe('my-custom-key-id')
        ->and($found->fingerprint)->toBe($keyEntity->fingerprint);
});

test('list all keys and count matches', function () {
    $ca = createTempCaForKeyTests();

    $keyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);

    $key1 = KeyBuilder::fresh($keyType)->make();
    $ca->keys->save($key1);

    $key2 = KeyBuilder::fresh($keyType)->make();
    $ca->keys->save($key2);

    $key3 = KeyBuilder::fresh($keyType)->make();
    $ca->keys->save($key3);

    $allKeys = $ca->keys->all();

    expect($allKeys)->toHaveCount(3)
        ->and($ca->keys->count())->toBe(3);
});

test('delete key and verify it is gone', function () {
    $ca = createTempCaForKeyTests();

    $keyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);
    $keyEntity = KeyBuilder::fresh($keyType)->make();
    $ca->keys->save($keyEntity);

    $id = $keyEntity->id;
    expect($ca->keys->has($id))->toBeTrue();

    $ca->keys->delete($id);

    expect($ca->keys->has($id))->toBeFalse()
        ->and($ca->keys->findOrNull($id))->toBeNull();
});

test('delete private key only and verify hasPrivateKey becomes false', function () {
    $ca = createTempCaForKeyTests();

    $keyType = new EdDSAKeyType(curve: EdDSACurve::Ed25519);
    $keyEntity = KeyBuilder::fresh($keyType)->make();
    $ca->keys->save($keyEntity);

    $id = $keyEntity->id;
    $found = $ca->keys->find($id);
    expect($found->hasPrivateKey)->toBeTrue()
        ->and($found->privateKey)->not->toBeNull();

    // Remove the private key
    $found->hasPrivateKey = false;
    $ca->keys->save($found);

    $reloaded = $ca->keys->find($id);

    expect($reloaded->hasPrivateKey)->toBeFalse()
        ->and($reloaded->privateKey)->toBeNull()
        ->and($reloaded->publicKey)->toContain('PUBLIC KEY');
});
