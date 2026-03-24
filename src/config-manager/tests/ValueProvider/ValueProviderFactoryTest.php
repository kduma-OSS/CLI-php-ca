<?php

declare(strict_types=1);

use KDuma\PhpCA\ConfigManager\ValueProvider\Base64ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\EnvValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ExplodeValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\FileValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\FirstValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\JsonValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\StringValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;

it('discovers all key discovery types', function () {
    $factory = new ValueProviderFactory;
    $types = $factory->getTypes();

    expect($types)->toHaveKeys(['string', 'base64', 'env', 'file', 'first', 'explode', 'json']);
});

it('creates StringValueProvider', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray(['type' => 'string', 'value' => 'my_secret'], '/base');

    expect($kd)->toBeInstanceOf(StringValueProvider::class)
        ->and($kd->resolve())->toBe('my_secret');
});

it('creates Base64ValueProvider', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray(['type' => 'base64', 'value' => base64_encode('raw_bytes')], '/base');

    expect($kd)->toBeInstanceOf(Base64ValueProvider::class)
        ->and($kd->resolve())->toBe('raw_bytes');
});

it('creates EnvValueProvider', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray(['type' => 'env', 'variable' => 'TEST_KEY_VAR'], '/base');

    expect($kd)->toBeInstanceOf(EnvValueProvider::class)
        ->and($kd->variable)->toBe('TEST_KEY_VAR');
});

it('creates FileValueProvider with resolved path', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray(['type' => 'file', 'path' => './keys/secret.key'], '/home/user');

    expect($kd)->toBeInstanceOf(FileValueProvider::class)
        ->and($kd->path)->toBe('/home/user/keys/secret.key');
});

it('throws on unknown type', function () {
    $factory = new ValueProviderFactory;
    $factory->fromArray(['type' => 'vault'], '/base');
})->throws(InvalidArgumentException::class, 'Unknown value provider type "vault"');

it('round-trips string key discovery to plain string', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray(['type' => 'string', 'value' => 'test'], '/base');

    expect($kd->toArray())->toBe('test');
});

it('creates StringValueProvider from plain string', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray('my_secret', '/base');

    expect($kd)->toBeInstanceOf(StringValueProvider::class)
        ->and($kd->resolve())->toBe('my_secret')
        ->and($kd->toArray())->toBe('my_secret');
});

it('round-trips base64 key discovery', function () {
    $encoded = base64_encode('test');
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray(['type' => 'base64', 'value' => $encoded], '/base');

    expect($kd->toArray())->toBe(['type' => 'base64', 'value' => $encoded]);
});

it('creates Base64ValueProvider with nested ValueProvider', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'base64',
        'value' => ['type' => 'string', 'value' => base64_encode('nested_bytes')],
    ], '/base');

    expect($kd)->toBeInstanceOf(Base64ValueProvider::class)
        ->and($kd->value)->toBeInstanceOf(StringValueProvider::class)
        ->and($kd->resolve())->toBe('nested_bytes');
});

it('creates Base64ValueProvider with nested env', function () {
    putenv('TEST_B64_KEY='.base64_encode('from_env'));

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'base64',
        'value' => ['type' => 'env', 'variable' => 'TEST_B64_KEY'],
    ], '/base');

    expect($kd->resolve())->toBe('from_env');

    putenv('TEST_B64_KEY');
});

it('round-trips base64 with nested key discovery', function () {
    $input = [
        'type' => 'base64',
        'value' => ['type' => 'env', 'variable' => 'MY_B64_KEY'],
    ];

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray($input, '/base');

    expect($kd->toArray())->toBe($input);
});

it('first key discovery resolves first successful candidate', function () {
    putenv('TEST_FIRST_A=value_a');
    putenv('TEST_FIRST_B=value_b');

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'first',
        'candidates' => [
            ['type' => 'env', 'variable' => 'TEST_FIRST_A'],
            ['type' => 'env', 'variable' => 'TEST_FIRST_B'],
        ],
    ], '/base');

    expect($kd)->toBeInstanceOf(FirstValueProvider::class)
        ->and($kd->resolve())->toBe('value_a');

    putenv('TEST_FIRST_A');
    putenv('TEST_FIRST_B');
});

it('first key discovery skips failing candidates', function () {
    putenv('TEST_FIRST_FALLBACK=fallback_value');

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'first',
        'candidates' => [
            ['type' => 'env', 'variable' => 'TEST_NONEXISTENT_VAR'],
            ['type' => 'env', 'variable' => 'TEST_FIRST_FALLBACK'],
        ],
    ], '/base');

    expect($kd->resolve())->toBe('fallback_value');

    putenv('TEST_FIRST_FALLBACK');
});

it('first key discovery throws when none resolve', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'first',
        'candidates' => [
            ['type' => 'env', 'variable' => 'TEST_NONE_1'],
            ['type' => 'env', 'variable' => 'TEST_NONE_2'],
        ],
    ], '/base');

    $kd->resolve();
})->throws(RuntimeException::class, 'None of the key discovery candidates resolved successfully.');

it('round-trips first key discovery', function () {
    $input = [
        'type' => 'first',
        'candidates' => [
            ['type' => 'env', 'variable' => 'PRIVATE_KEY'],
            ['type' => 'env', 'variable' => 'PUBLIC_KEY'],
        ],
    ];

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray($input, '/base');

    expect($kd->toArray())->toBe($input);
});

it('explode extracts part by index', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'explode',
        'index' => 0,
        'separator' => ':',
        'value' => 'user:pass',
    ], '/base');

    expect($kd)->toBeInstanceOf(ExplodeValueProvider::class)
        ->and($kd->resolve())->toBe('user');
});

it('explode extracts second part', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'explode',
        'index' => 1,
        'separator' => ':',
        'value' => 'user:pass',
    ], '/base');

    expect($kd->resolve())->toBe('pass');
});

it('explode works with nested env key discovery', function () {
    putenv('TEST_CREDENTIALS=admin:s3cret');

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'explode',
        'index' => 1,
        'separator' => ':',
        'value' => ['type' => 'env', 'variable' => 'TEST_CREDENTIALS'],
    ], '/base');

    expect($kd->resolve())->toBe('s3cret');

    putenv('TEST_CREDENTIALS');
});

it('explode throws on out-of-bounds index', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'explode',
        'index' => 5,
        'separator' => ':',
        'value' => 'user:pass',
    ], '/base');

    $kd->resolve();
})->throws(RuntimeException::class, 'index 5 out of bounds');

it('round-trips explode key discovery', function () {
    $input = [
        'type' => 'explode',
        'index' => 0,
        'separator' => ':',
        'value' => ['type' => 'env', 'variable' => 'CREDS'],
    ];

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray($input, '/base');

    expect($kd->toArray())->toBe($input);
});

it('round-trips explode with string shorthand value', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'explode',
        'index' => 0,
        'separator' => ':',
        'value' => 'user:pass',
    ], '/base');

    expect($kd->toArray())->toBe([
        'type' => 'explode',
        'index' => 0,
        'separator' => ':',
        'value' => 'user:pass',
    ]);
});

it('json extracts nested object value', function () {
    $json = json_encode(['credentials' => ['password' => 's3cret']]);

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'json',
        'path' => 'credentials.password',
        'value' => $json,
    ], '/base');

    expect($kd)->toBeInstanceOf(JsonValueProvider::class)
        ->and($kd->resolve())->toBe('s3cret');
});

it('json extracts array index value', function () {
    $json = json_encode(['users' => [['key' => 'first'], ['key' => 'second']]]);

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'json',
        'path' => 'users.1.key',
        'value' => $json,
    ], '/base');

    expect($kd->resolve())->toBe('second');
});

it('json works with nested env key discovery', function () {
    $json = json_encode(['db' => ['pass' => 'from_env']]);
    putenv('TEST_JSON_CONFIG='.$json);

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'json',
        'path' => 'db.pass',
        'value' => ['type' => 'env', 'variable' => 'TEST_JSON_CONFIG'],
    ], '/base');

    expect($kd->resolve())->toBe('from_env');

    putenv('TEST_JSON_CONFIG');
});

it('json throws on missing path', function () {
    $json = json_encode(['a' => 'b']);

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'json',
        'path' => 'missing.path',
        'value' => $json,
    ], '/base');

    $kd->resolve();
})->throws(RuntimeException::class, 'path "missing.path" not found');

it('json throws on non-string value at path', function () {
    $json = json_encode(['data' => ['nested' => ['a' => 1, 'b' => 2]]]);

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'json',
        'path' => 'data.nested',
        'value' => $json,
    ], '/base');

    $kd->resolve();
})->throws(RuntimeException::class, 'is not a string');

it('round-trips json key discovery', function () {
    $input = [
        'type' => 'json',
        'path' => 'credentials.password',
        'value' => ['type' => 'env', 'variable' => 'CONFIG_JSON'],
    ];

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray($input, '/base');

    expect($kd->toArray())->toBe($input);
});

it('round-trips json with string shorthand value', function () {
    $json = json_encode(['key' => 'val']);

    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray([
        'type' => 'json',
        'path' => 'key',
        'value' => $json,
    ], '/base');

    expect($kd->toArray())->toBe([
        'type' => 'json',
        'path' => 'key',
        'value' => $json,
    ]);
});

it('round-trips env key discovery', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray(['type' => 'env', 'variable' => 'MY_KEY'], '/base');

    expect($kd->toArray())->toBe(['type' => 'env', 'variable' => 'MY_KEY']);
});

it('round-trips file key discovery', function () {
    $factory = new ValueProviderFactory;
    $kd = $factory->fromArray(['type' => 'file', 'path' => '/absolute/key.pem'], '/base');

    expect($kd->toArray())->toBe(['type' => 'file', 'path' => '/absolute/key.pem']);
});
