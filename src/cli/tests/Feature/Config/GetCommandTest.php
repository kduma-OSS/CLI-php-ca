<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->configPath = sys_get_temp_dir() . '/php-ca-test-' . uniqid() . '.json';
    file_put_contents($this->configPath, json_encode([
        'adapter' => ['type' => 'directory', 'path' => './data'],
    ]));
});

afterEach(function () {
    if (file_exists($this->configPath)) {
        unlink($this->configPath);
    }
});

it('gets scalar value by dot-path', function () {
    $this->artisan('config:get', ['key' => 'adapter.type', '-c' => $this->configPath])
        ->expectsOutputToContain('directory')
        ->assertExitCode(0);
});

it('gets nested object as JSON', function () {
    $this->artisan('config:get', ['key' => 'adapter', '-c' => $this->configPath])
        ->expectsOutputToContain('"type": "directory"')
        ->assertExitCode(0);
});

it('outputs compact JSON with --compact', function () {
    $this->artisan('config:get', ['key' => 'adapter', '--compact' => true, '-c' => $this->configPath])
        ->expectsOutputToContain('{"type":"directory","path":"./data"}')
        ->assertExitCode(0);
});

it('fails on missing key', function () {
    $this->artisan('config:get', ['key' => 'missing.key', '-c' => $this->configPath])
        ->assertExitCode(1);
});
