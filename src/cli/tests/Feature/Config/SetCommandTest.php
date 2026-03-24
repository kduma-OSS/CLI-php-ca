<?php

declare(strict_types=1);
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->configPath = sys_get_temp_dir().'/php-ca-test-'.uniqid().'.json';
    file_put_contents($this->configPath, json_encode([
        'adapter' => ['type' => 'directory', 'path' => './data'],
    ]));
});

afterEach(function () {
    if (file_exists($this->configPath)) {
        unlink($this->configPath);
    }
});

it('sets scalar value', function () {
    $this->artisan('config:set', ['key' => 'adapter.path', 'value' => '/new/path', '-c' => $this->configPath])
        ->assertExitCode(0);

    $data = json_decode(file_get_contents($this->configPath), true);
    expect($data['adapter']['path'])->toBe('/new/path');
});

it('sets JSON object value', function () {
    $this->artisan('config:set', ['key' => 'adapter', 'value' => '{"type":"sqlite","path":"./db.sqlite"}', '-c' => $this->configPath])
        ->assertExitCode(0);

    $data = json_decode(file_get_contents($this->configPath), true);
    expect($data['adapter']['type'])->toBe('sqlite')
        ->and($data['adapter']['path'])->toBe('./db.sqlite');
});

it('creates nested keys that do not exist', function () {
    $this->artisan('config:set', ['key' => 'integrity.hasher.type', 'value' => 'sha256', '-c' => $this->configPath])
        ->assertExitCode(0);

    $data = json_decode(file_get_contents($this->configPath), true);
    expect($data['integrity']['hasher']['type'])->toBe('sha256');
});
