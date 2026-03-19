<?php

declare(strict_types=1);

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\ConfigManager\Adapter\DirectoryAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\CaConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\EncryptionConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\EncryptionRuleConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\SecretBoxAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\IntegrityConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Sha256HasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\HmacSha256SignerConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\StringValueProvider;

function recursiveDelete(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($dir);
}

test('createCertificationAuthority with directory adapter only', function () {
    $tmpDir = sys_get_temp_dir() . '/ca-test-' . uniqid();
    mkdir($tmpDir, 0777, true);

    try {
        $adapter = new DirectoryAdapterConfiguration(path: $tmpDir);
        $config = new CaConfiguration(adapter: $adapter);

        $ca = $config->createCertificationAuthority();

        expect($ca)->toBeInstanceOf(CertificationAuthority::class);
    } finally {
        recursiveDelete($tmpDir);
    }
});

test('createCertificationAuthority with integrity configuration', function () {
    $tmpDir = sys_get_temp_dir() . '/ca-test-' . uniqid();
    mkdir($tmpDir, 0777, true);

    try {
        $adapter = new DirectoryAdapterConfiguration(path: $tmpDir);
        $hasher = new Sha256HasherConfiguration();
        $signer = new HmacSha256SignerConfiguration(
            id: 'test-signer',
            secret: new StringValueProvider('my-secret-key-for-hmac'),
        );
        $integrity = new IntegrityConfiguration(hasher: $hasher, signer: $signer);
        $config = new CaConfiguration(adapter: $adapter, integrity: $integrity);

        $ca = $config->createCertificationAuthority();

        expect($ca)->toBeInstanceOf(CertificationAuthority::class);
    } finally {
        recursiveDelete($tmpDir);
    }
});

test('createCertificationAuthority with encryption configuration', function () {
    $tmpDir = sys_get_temp_dir() . '/ca-test-' . uniqid();
    mkdir($tmpDir, 0777, true);

    try {
        $key = str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $adapter = new DirectoryAdapterConfiguration(path: $tmpDir);
        $secretBox = new SecretBoxAlgorithmConfiguration(
            id: 'master',
            key: new StringValueProvider($key),
        );
        $rule = new EncryptionRuleConfiguration(
            keyId: 'master',
            entityName: 'keys',
        );
        $encryption = new EncryptionConfiguration(keys: [$secretBox], rules: [$rule]);
        $config = new CaConfiguration(adapter: $adapter, encryption: $encryption);

        $ca = $config->createCertificationAuthority();

        expect($ca)->toBeInstanceOf(CertificationAuthority::class);
    } finally {
        recursiveDelete($tmpDir);
    }
});

test('createCertificationAuthority with both integrity and encryption', function () {
    $tmpDir = sys_get_temp_dir() . '/ca-test-' . uniqid();
    mkdir($tmpDir, 0777, true);

    try {
        $adapter = new DirectoryAdapterConfiguration(path: $tmpDir);

        $hasher = new Sha256HasherConfiguration();
        $integrity = new IntegrityConfiguration(hasher: $hasher);

        $key = str_repeat('b', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $secretBox = new SecretBoxAlgorithmConfiguration(
            id: 'enc-key',
            key: new StringValueProvider($key),
        );
        $rule = new EncryptionRuleConfiguration(
            keyId: 'enc-key',
            entityName: 'certs',
        );
        $encryption = new EncryptionConfiguration(keys: [$secretBox], rules: [$rule]);

        $config = new CaConfiguration(
            adapter: $adapter,
            integrity: $integrity,
            encryption: $encryption,
        );

        $ca = $config->createCertificationAuthority();

        expect($ca)->toBeInstanceOf(CertificationAuthority::class);
    } finally {
        recursiveDelete($tmpDir);
    }
});
