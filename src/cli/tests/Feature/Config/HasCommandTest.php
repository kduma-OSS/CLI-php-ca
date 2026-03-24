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

it('returns true and exit 0 for existing key', function () {
    $this->artisan('config:has', ['key' => 'adapter.type', '-c' => $this->configPath])
        ->expectsOutputToContain('true')
        ->assertExitCode(0);
});

it('returns false and exit 1 for missing key', function () {
    $this->artisan('config:has', ['key' => 'missing.key', '-c' => $this->configPath])
        ->expectsOutputToContain('false')
        ->assertExitCode(1);
});
