<?php

declare(strict_types=1);

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Entity\CACsrBuilder;
use KDuma\PhpCA\Entity\CACsrEntity;
use KDuma\PhpCA\Entity\KeyBuilder;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateSubject\DN\CommonName;
use KDuma\PhpCA\Record\CertificateSubject\DN\Country;
use KDuma\PhpCA\Record\CertificateSubject\DN\Organization;
use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;
use KDuma\SimpleDAL\Adapter\Flysystem\FlysystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

// --- CACsrEntity immutability and property tests ---

test('CACsrEntity: subject property is immutable after set', function () {
    $entity = new CACsrEntity;
    $subject = new CertificateSubject([new CommonName('First')]);
    $entity->subject = $subject;

    expect($entity->subject->toString())->toContain('First');

    $this->expectException(LogicException::class);
    $entity->subject = new CertificateSubject([new CommonName('Second')]);
});

test('CACsrEntity: keyId property is immutable after set', function () {
    $entity = new CACsrEntity;
    $entity->keyId = 'key-1';

    expect($entity->keyId)->toBe('key-1');

    $this->expectException(LogicException::class);
    $entity->keyId = 'key-2';
});

test('CACsrEntity: fingerprint property is immutable after set', function () {
    $entity = new CACsrEntity;
    $entity->fingerprint = 'fp-1';

    expect($entity->fingerprint)->toBe('fp-1');

    $this->expectException(LogicException::class);
    $entity->fingerprint = 'fp-2';
});

test('CACsrEntity: requestedExtensions is immutable after set with non-empty value', function () {
    $entity = new CACsrEntity;
    $ext = new BasicConstraintsExtension(ca: true);
    $entity->requestedExtensions = [$ext];

    expect($entity->requestedExtensions)->toHaveCount(1);

    $this->expectException(LogicException::class);
    $entity->requestedExtensions = [new BasicConstraintsExtension(ca: false)];
});

test('CACsrEntity: caCertificateId is mutable', function () {
    $entity = new CACsrEntity;
    $entity->caCertificateId = 'cert-1';
    $entity->caCertificateId = 'cert-2';

    expect($entity->caCertificateId)->toBe('cert-2');
});

test('CACsrEntity: caCertificateId defaults to null', function () {
    $entity = new CACsrEntity;

    expect($entity->caCertificateId)->toBeNull();
});

test('CACsrEntity: requestedExtensions defaults to empty array', function () {
    $entity = new CACsrEntity;

    expect($entity->requestedExtensions)->toBe([]);
});

test('CACsrEntity: getSubjectString returns subject toString', function () {
    $entity = new CACsrEntity;
    $entity->subject = new CertificateSubject([
        new CommonName('Test CA'),
        new Organization('Test Org'),
    ]);

    expect($entity->getSubjectString())->toBe('CN=Test CA, O=Test Org');
});

test('CACsrEntity: csr property can be set before persistence', function () {
    $entity = new CACsrEntity;
    $entity->csr = '-----BEGIN CERTIFICATE REQUEST-----test-----END CERTIFICATE REQUEST-----';

    expect($entity->csr)->toContain('CERTIFICATE REQUEST');
});

test('CACsrEntity: id can be set before persistence', function () {
    $entity = new CACsrEntity;
    $entity->id = 'custom-id';

    expect($entity->id)->toBe('custom-id');
});

test('CACsrEntity: persisted is false for new entity', function () {
    $entity = new CACsrEntity;

    expect($entity->persisted)->toBeFalse();
});

test('CACsrEntity: multiple properties can be set', function () {
    $entity = new CACsrEntity;
    $subject = new CertificateSubject([
        new CommonName('Multi-prop CA'),
        new Organization('Test Org'),
        new Country('US'),
    ]);

    $entity->subject = $subject;
    $entity->keyId = 'key-abc';
    $entity->fingerprint = 'fp-xyz';
    $entity->caCertificateId = 'cert-123';
    $entity->requestedExtensions = [new BasicConstraintsExtension(ca: true)];
    $entity->csr = '-----BEGIN CERTIFICATE REQUEST-----data-----END CERTIFICATE REQUEST-----';

    expect($entity->subject->toString())->toBe('CN=Multi-prop CA, O=Test Org, C=US')
        ->and($entity->keyId)->toBe('key-abc')
        ->and($entity->fingerprint)->toBe('fp-xyz')
        ->and($entity->caCertificateId)->toBe('cert-123')
        ->and($entity->requestedExtensions)->toHaveCount(1)
        ->and($entity->csr)->toContain('CERTIFICATE REQUEST');
});

// --- CACsrBuilder unit tests ---

test('CACsrBuilder: builder methods return self for chaining', function () {
    $ca = createMinimalCa();
    $builder = new CACsrBuilder($ca);

    $result1 = $builder->id('test');
    $result2 = $builder->key('some-key-id');
    $result3 = $builder->subject(new CertificateSubject([new CommonName('test')]));
    $result4 = $builder->addExtension(new BasicConstraintsExtension(ca: true));

    expect($result1)->toBe($builder)
        ->and($result2)->toBe($builder)
        ->and($result3)->toBe($builder)
        ->and($result4)->toBe($builder);
});

test('CACsrBuilder: save requires key', function () {
    $ca = createMinimalCa();

    $builder = new CACsrBuilder($ca);
    $builder->subject(new CertificateSubject([new CommonName('No Key')]));
    $builder->save();
})->throws(LogicException::class, 'Key is required');

test('CACsrBuilder: save requires subject', function () {
    $ca = createMinimalCa();

    $keyType = new EdDSAKeyType(
        curve: EdDSACurve::Ed25519,
    );
    $keyEntity = KeyBuilder::fresh($keyType)->make();
    $ca->keys->save($keyEntity);

    $builder = new CACsrBuilder($ca);
    $builder->key($keyEntity);
    $builder->save();
})->throws(LogicException::class, 'Subject is required');

function createMinimalCa(): CertificationAuthority
{
    $tempDir = sys_get_temp_dir().'/php-ca-test-'.uniqid();
    mkdir($tempDir, 0777, true);
    $filesystem = new Filesystem(
        new LocalFilesystemAdapter($tempDir)
    );
    $adapter = new FlysystemAdapter($filesystem);
    ExtensionRegistry::registerDefaults();

    return new CertificationAuthority($adapter);
}
