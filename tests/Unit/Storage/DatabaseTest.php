<?php

use App\Storage\Database;
use App\Storage\Entities\Certificate;
use App\Storage\Entities\Key;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/php-ca-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
    $this->db = new Database($this->tempDir);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        (new Illuminate\Filesystem\Filesystem)->deleteDirectory($this->tempDir);
    }
});

// --- Key CRUD ---

test('can save and retrieve a key', function () {
    $key = new Key(
        id: 'webserver-key',
        size: 4096,
        fingerprint: 'AA:BB:CC:DD',
        createdAt: CarbonImmutable::parse('2024-01-15T10:00:00Z'),
    );

    $this->db->keys()->save($key);

    $found = $this->db->keys()->find('webserver-key');
    expect($found)->not->toBeNull()
        ->and($found->id)->toBe('webserver-key')
        ->and($found->size)->toBe(4096)
        ->and($found->fingerprint)->toBe('AA:BB:CC:DD')
        ->and($found->createdAt->toIso8601String())->toBe(CarbonImmutable::parse('2024-01-15T10:00:00Z')->toIso8601String());
});

test('find returns null for nonexistent key', function () {
    expect($this->db->keys()->find('nonexistent'))->toBeNull();
});

test('findOrFail throws for nonexistent key', function () {
    $this->db->keys()->findOrFail('nonexistent');
})->throws(RuntimeException::class);

test('can list all keys', function () {
    $this->db->keys()->save(new Key('key-a', 2048, 'AA:BB:CC:DD', CarbonImmutable::parse('2024-01-01T00:00:00Z')));
    $this->db->keys()->save(new Key('key-b', null, 'EE:FF:00:11', CarbonImmutable::parse('2024-02-01T00:00:00Z')));

    $all = $this->db->keys()->all();
    expect($all)->toHaveCount(2);
    expect($all->pluck('id')->sort()->values()->all())->toBe(['key-a', 'key-b']);
});

test('can delete a key', function () {
    $this->db->keys()->save(new Key('to-delete', 2048, 'AA:BB:CC:DD', CarbonImmutable::parse('2024-01-01T00:00:00Z')));
    expect($this->db->keys()->exists('to-delete'))->toBeTrue();

    $result = $this->db->keys()->delete('to-delete');
    expect($result)->toBeTrue();
    expect($this->db->keys()->exists('to-delete'))->toBeFalse();
});

test('delete returns false for nonexistent key', function () {
    expect($this->db->keys()->delete('nonexistent'))->toBeFalse();
});

test('exists returns correct values', function () {
    expect($this->db->keys()->exists('test-key'))->toBeFalse();

    $this->db->keys()->save(new Key('test-key', 2048, 'AA:BB:CC:DD', CarbonImmutable::parse('2024-01-01T00:00:00Z')));
    expect($this->db->keys()->exists('test-key'))->toBeTrue();
});

// --- Certificate CRUD ---

test('can save and retrieve a certificate', function () {
    $cert = new Certificate(
        id: 'webserver-2024',
        keyId: 'webserver-key',
        commonName: 'example.com',
        type: 'server',
        serialNumber: 'AABBCCDD',
        notBefore: CarbonImmutable::parse('2024-01-01T00:00:00Z'),
        notAfter: CarbonImmutable::parse('2025-01-01T00:00:00Z'),
        subjectAltNames: ['example.com', 'www.example.com'],
        extensions: ['digitalSignature', 'keyEncipherment'],
    );

    $this->db->certificates()->save($cert);

    $found = $this->db->certificates()->find('webserver-2024');
    expect($found)->not->toBeNull()
        ->and($found->id)->toBe('webserver-2024')
        ->and($found->keyId)->toBe('webserver-key')
        ->and($found->commonName)->toBe('example.com')
        ->and($found->type)->toBe('server')
        ->and($found->serialNumber)->toBe('AABBCCDD')
        ->and($found->subjectAltNames)->toBe(['example.com', 'www.example.com']);
});

test('forKey filters certificates by key', function () {
    $this->db->certificates()->save(new Certificate(
        'cert-a', 'key-1', 'a.com', 'server', 'AA', CarbonImmutable::parse('2024-01-01T00:00:00Z'), CarbonImmutable::parse('2025-01-01T00:00:00Z'),
    ));
    $this->db->certificates()->save(new Certificate(
        'cert-b', 'key-2', 'b.com', 'server', 'BB', CarbonImmutable::parse('2024-01-01T00:00:00Z'), CarbonImmutable::parse('2025-01-01T00:00:00Z'),
    ));
    $this->db->certificates()->save(new Certificate(
        'cert-c', 'key-1', 'c.com', 'server', 'CC', CarbonImmutable::parse('2024-01-01T00:00:00Z'), CarbonImmutable::parse('2025-01-01T00:00:00Z'),
    ));

    $forKey1 = $this->db->certificates()->forKey('key-1');
    expect($forKey1)->toHaveCount(2);
    expect($forKey1->pluck('id')->sort()->values()->all())->toBe(['cert-a', 'cert-c']);

    $forKey2 = $this->db->certificates()->forKey('key-2');
    expect($forKey2)->toHaveCount(1);
    expect($forKey2->first()->id)->toBe('cert-b');
});

// --- PEM files ---

test('can write and read PEM files for keys', function () {
    $this->db->keys()->save(new Key('my-key', 2048, 'AA:BB:CC:DD', CarbonImmutable::parse('2024-01-01T00:00:00Z')));
    $pemContent = "-----BEGIN PRIVATE KEY-----\nMIItest...\n-----END PRIVATE KEY-----\n";

    $this->db->keys()->putFile('my-key', 'private.key', $pemContent);

    expect($this->db->keys()->hasFile('my-key', 'private.key'))->toBeTrue();
    expect($this->db->keys()->getFile('my-key', 'private.key'))->toBe($pemContent);
    expect($this->db->keys()->getFile('my-key', 'public.key'))->toBeNull();
});

test('can write and read PEM files for certificates', function () {
    $cert = new Certificate(
        'my-cert', 'my-key', 'example.com', 'server', 'AA', CarbonImmutable::parse('2024-01-01T00:00:00Z'), CarbonImmutable::parse('2025-01-01T00:00:00Z'),
    );
    $this->db->certificates()->save($cert);

    $certPem = "-----BEGIN CERTIFICATE-----\nMIItest...\n-----END CERTIFICATE-----\n";
    $csrPem = "-----BEGIN CERTIFICATE REQUEST-----\nMIItest...\n-----END CERTIFICATE REQUEST-----\n";

    $this->db->certificates()->putFile('my-cert', 'certificate.pem', $certPem);
    $this->db->certificates()->putFile('my-cert', 'request.pem', $csrPem);

    expect($this->db->certificates()->getFile('my-cert', 'certificate.pem'))->toBe($certPem);
    expect($this->db->certificates()->getFile('my-cert', 'request.pem'))->toBe($csrPem);
    expect($this->db->certificates()->hasFile('my-cert', 'request.pem'))->toBeTrue();
});

// --- Allowed files validation ---

test('putFile rejects disallowed filename in repository', function () {
    $this->db->keys()->save(new Key('my-key', 2048, 'AA:BB:CC:DD', CarbonImmutable::parse('2024-01-01T00:00:00Z')));
    $this->db->keys()->putFile('my-key', 'evil.txt', 'data');
})->throws(InvalidArgumentException::class, 'File [evil.txt] is not allowed in [keys]');

test('getFile rejects disallowed filename in repository', function () {
    $this->db->keys()->save(new Key('my-key', 2048, 'AA:BB:CC:DD', CarbonImmutable::parse('2024-01-01T00:00:00Z')));
    $this->db->keys()->getFile('my-key', '../metadata.json');
})->throws(InvalidArgumentException::class, 'File [../metadata.json] is not allowed in [keys]');

test('hasFile rejects disallowed filename in repository', function () {
    $this->db->keys()->save(new Key('my-key', 2048, 'AA:BB:CC:DD', CarbonImmutable::parse('2024-01-01T00:00:00Z')));
    $this->db->keys()->hasFile('my-key', 'secret.pem');
})->throws(InvalidArgumentException::class, 'File [secret.pem] is not allowed in [keys]');
// --- JSON format ---

test('metadata json has sorted keys and trailing newline', function () {
    $key = new Key('format-test', 2048, 'AA:BB:CC:DD', CarbonImmutable::parse('2024-01-01T00:00:00Z'));
    $this->db->keys()->save($key);

    $jsonPath = $this->tempDir.'/keys/format-test/metadata.json';
    $content = file_get_contents($jsonPath);

    // Trailing newline
    expect($content)->toEndWith("\n");

    // Sorted keys
    $data = json_decode($content, true);
    $keys = array_keys($data);
    $sorted = $keys;
    sort($sorted);
    expect($keys)->toBe($sorted);

    // Pretty printed
    expect($content)->toContain("\n    ");
});

// --- CA metadata and files ---

test('can save and read CA metadata', function () {
    $this->db->saveCaMetadata(['name' => 'My Root CA', 'type' => 'root']);

    $meta = $this->db->caMetadata();
    expect($meta)->toBe(['name' => 'My Root CA', 'type' => 'root']);
});

test('caMetadata returns null when no ca.json exists', function () {
    expect($this->db->caMetadata())->toBeNull();
});

test('can save and read CA certificate', function () {
    $pem = "-----BEGIN CERTIFICATE-----\nCA cert\n-----END CERTIFICATE-----\n";
    $this->db->saveCaCertificate($pem);
    expect($this->db->caCertificate())->toBe($pem);
});

test('caCertificate returns null when not set', function () {
    expect($this->db->caCertificate())->toBeNull();
});

test('can save and read CA key', function () {
    $pem = "-----BEGIN PRIVATE KEY-----\nCA key\n-----END PRIVATE KEY-----\n";
    $this->db->saveCaKey($pem);
    expect($this->db->caKey())->toBe($pem);
});

test('caKey returns null when not set', function () {
    expect($this->db->caKey())->toBeNull();
});

test('can save and read CA CSR', function () {
    $pem = "-----BEGIN CERTIFICATE REQUEST-----\nCA csr\n-----END CERTIFICATE REQUEST-----\n";
    $this->db->saveCaCsr($pem);
    expect($this->db->caCsr())->toBe($pem);
});

test('caCsr returns null when not set', function () {
    expect($this->db->caCsr())->toBeNull();
});

// --- Two independent databases ---

test('two database instances are independent', function () {
    $dir2 = sys_get_temp_dir().'/php-ca-test-'.uniqid();
    mkdir($dir2, 0755, true);

    $db2 = new Database($dir2);

    $this->db->keys()->save(new Key('shared-name', 2048, 'AA:BB:CC:DD', CarbonImmutable::parse('2024-01-01T00:00:00Z')));
    $db2->keys()->save(new Key('shared-name', null, 'EE:FF:00:11', CarbonImmutable::parse('2024-06-01T00:00:00Z')));

    $fromDb1 = $this->db->keys()->find('shared-name');
    $fromDb2 = $db2->keys()->find('shared-name');

    expect($fromDb1->size)->toBe(2048);
    expect($fromDb2->size)->toBeNull();

    (new Illuminate\Filesystem\Filesystem)->deleteDirectory($dir2);
});

// --- Query ---

test('query returns collection for chaining', function () {
    $this->db->certificates()->save(new Certificate(
        'active-cert', 'key-1', 'a.com', 'server', 'AA', CarbonImmutable::parse('2024-01-01T00:00:00Z'), CarbonImmutable::parse('2025-01-01T00:00:00Z'),
    ));
    $this->db->certificates()->save(new Certificate(
        'revoked-cert', 'key-1', 'b.com', 'server', 'BB', CarbonImmutable::parse('2024-01-01T00:00:00Z'), CarbonImmutable::parse('2025-01-01T00:00:00Z'),
        revokedAt: CarbonImmutable::parse('2024-06-01T00:00:00Z'),
    ));

    $active = $this->db->certificates()->query()
        ->filter(fn (Certificate $c) => $c->revokedAt === null);

    expect($active)->toHaveCount(1);
    expect($active->first()->id)->toBe('active-cert');
});
