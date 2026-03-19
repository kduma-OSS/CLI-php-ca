<?php

declare(strict_types=1);

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Entity\KeyBuilder;
use KDuma\PhpCA\Entity\KeyEntity;
use KDuma\PhpCA\Record\KeyType\ECDSAKeyType;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\RSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\EcCurve;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;
use KDuma\PhpCA\Tests\Support\InMemoryStorageAdapter;

function createTempCa(): CertificationAuthority
{
    $adapter = new InMemoryStorageAdapter();
    return new CertificationAuthority($adapter);
}

// --- RSA Key ---

test('KeyBuilder::fresh with RSA key type creates valid entity', function () {
    $entity = KeyBuilder::fresh(new RSAKeyType(2048))->make();

    expect($entity)->toBeInstanceOf(KeyEntity::class)
        ->and($entity->type)->toBeInstanceOf(RSAKeyType::class)
        ->and($entity->type->size)->toBe(2048)
        ->and($entity->hasPrivateKey)->toBeTrue()
        ->and($entity->publicKey)->toStartWith('-----BEGIN PUBLIC KEY-----')
        ->and($entity->privateKey)->toStartWith('-----BEGIN PRIVATE KEY-----');
});

// --- EdDSA Key ---

test('KeyBuilder::fresh with EdDSA Ed25519 key type creates valid entity', function () {
    $entity = KeyBuilder::fresh(new EdDSAKeyType(EdDSACurve::Ed25519))->make();

    expect($entity)->toBeInstanceOf(KeyEntity::class)
        ->and($entity->type)->toBeInstanceOf(EdDSAKeyType::class)
        ->and($entity->type->curve)->toBe(EdDSACurve::Ed25519)
        ->and($entity->hasPrivateKey)->toBeTrue()
        ->and($entity->publicKey)->toStartWith('-----BEGIN PUBLIC KEY-----')
        ->and($entity->privateKey)->toStartWith('-----BEGIN PRIVATE KEY-----');
});

// --- ECDSA Key ---

test('KeyBuilder::fresh with ECDSA Secp256r1 key type creates valid entity', function () {
    $entity = KeyBuilder::fresh(new ECDSAKeyType(EcCurve::Secp256r1))->make();

    expect($entity)->toBeInstanceOf(KeyEntity::class)
        ->and($entity->type)->toBeInstanceOf(ECDSAKeyType::class)
        ->and($entity->type->curve)->toBe(EcCurve::Secp256r1)
        ->and($entity->hasPrivateKey)->toBeTrue()
        ->and($entity->publicKey)->toStartWith('-----BEGIN PUBLIC KEY-----')
        ->and($entity->privateKey)->toStartWith('-----BEGIN PRIVATE KEY-----');
});

// --- Fingerprint format ---

test('entity fingerprint is a 64-char hex string', function () {
    $entity = KeyBuilder::fresh(new EdDSAKeyType(EdDSACurve::Ed25519))->make();

    expect($entity->fingerprint)->toMatch('/^[a-f0-9]{64}$/');
});

// --- ID auto-set from fingerprint ---

test('entity ID is auto-set from fingerprint', function () {
    $entity = KeyBuilder::fresh(new EdDSAKeyType(EdDSACurve::Ed25519))->make();

    expect($entity->id)->toBe($entity->fingerprint);
});

// --- fromExisting detects correct type ---

test('fromExisting with RSA private key PEM detects RSA type', function () {
    $rsaEntity = KeyBuilder::fresh(new RSAKeyType(2048))->make();
    $restored = KeyBuilder::fromExisting($rsaEntity->privateKey)->make();

    expect($restored->type)->toBeInstanceOf(RSAKeyType::class)
        ->and($restored->type->size)->toBe(2048)
        ->and($restored->hasPrivateKey)->toBeTrue();
});

test('fromExisting with EdDSA private key PEM detects EdDSA type', function () {
    $eddsaEntity = KeyBuilder::fresh(new EdDSAKeyType(EdDSACurve::Ed25519))->make();
    $restored = KeyBuilder::fromExisting($eddsaEntity->privateKey)->make();

    expect($restored->type)->toBeInstanceOf(EdDSAKeyType::class)
        ->and($restored->type->curve)->toBe(EdDSACurve::Ed25519)
        ->and($restored->hasPrivateKey)->toBeTrue();
});

test('fromExisting with ECDSA private key PEM detects ECDSA type', function () {
    $ecdsaEntity = KeyBuilder::fresh(new ECDSAKeyType(EcCurve::Secp256r1))->make();
    $restored = KeyBuilder::fromExisting($ecdsaEntity->privateKey)->make();

    expect($restored->type)->toBeInstanceOf(ECDSAKeyType::class)
        ->and($restored->type->curve)->toBe(EcCurve::Secp256r1)
        ->and($restored->hasPrivateKey)->toBeTrue();
});

test('fromExisting with public key PEM sets hasPrivateKey to false', function () {
    $entity = KeyBuilder::fresh(new EdDSAKeyType(EdDSACurve::Ed25519))->make();
    $publicOnly = KeyBuilder::fromExisting($entity->publicKey)->make();

    expect($publicOnly->hasPrivateKey)->toBeFalse()
        ->and($publicOnly->privateKey)->toBeNull();
});

// --- Save and retrieve from collection ---

test('saving and retrieving key from collection', function () {
    $ca = createTempCa();
    $entity = KeyBuilder::fresh(new EdDSAKeyType(EdDSACurve::Ed25519))->make();

    $ca->keys->save($entity);

    expect($entity->persisted)->toBeTrue()
        ->and($entity->id)->toBe($entity->fingerprint);

    $retrieved = $ca->keys->find($entity->id);

    expect($retrieved)->toBeInstanceOf(KeyEntity::class)
        ->and($retrieved->fingerprint)->toBe($entity->fingerprint)
        ->and($retrieved->type)->toBeInstanceOf(EdDSAKeyType::class)
        ->and($retrieved->type->curve)->toBe(EdDSACurve::Ed25519)
        ->and($retrieved->hasPrivateKey)->toBeTrue()
        ->and($retrieved->publicKey)->toBe($entity->publicKey)
        ->and($retrieved->privateKey)->toBe($entity->privateKey);
});

test('saving RSA key and retrieving preserves key data', function () {
    $ca = createTempCa();
    $entity = KeyBuilder::fresh(new RSAKeyType(2048))->make();

    $ca->keys->save($entity);
    $retrieved = $ca->keys->find($entity->id);

    expect($retrieved->type)->toBeInstanceOf(RSAKeyType::class)
        ->and($retrieved->type->size)->toBe(2048)
        ->and($retrieved->publicKey)->toBe($entity->publicKey)
        ->and($retrieved->privateKey)->toBe($entity->privateKey);
});

test('collection count reflects saved keys', function () {
    $ca = createTempCa();

    expect($ca->keys->count())->toBe(0);

    $entity1 = KeyBuilder::fresh(new EdDSAKeyType(EdDSACurve::Ed25519))->make();
    $ca->keys->save($entity1);

    expect($ca->keys->count())->toBe(1);

    $entity2 = KeyBuilder::fresh(new ECDSAKeyType(EcCurve::Secp256r1))->make();
    $ca->keys->save($entity2);

    expect($ca->keys->count())->toBe(2);
});

test('collection has() checks for key existence', function () {
    $ca = createTempCa();
    $entity = KeyBuilder::fresh(new EdDSAKeyType(EdDSACurve::Ed25519))->make();

    expect($ca->keys->has($entity->fingerprint))->toBeFalse();

    $ca->keys->save($entity);

    expect($ca->keys->has($entity->fingerprint))->toBeTrue();
});
