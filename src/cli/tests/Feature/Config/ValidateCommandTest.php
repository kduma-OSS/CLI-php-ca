<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->configPath = sys_get_temp_dir() . '/php-ca-test-' . uniqid() . '.json';
});

afterEach(function () {
    if (file_exists($this->configPath)) {
        unlink($this->configPath);
    }
});

it('passes for valid config', function () {
    file_put_contents($this->configPath, json_encode([
        'adapter' => ['type' => 'directory', 'path' => './data'],
    ]));

    $this->artisan('config:validate', ['-c' => $this->configPath])
        ->assertExitCode(0);
});

it('fails for invalid config', function () {
    file_put_contents($this->configPath, json_encode([
        'not_adapter' => [],
    ]));

    $this->artisan('config:validate', ['-c' => $this->configPath])
        ->assertExitCode(1);
});
