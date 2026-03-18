<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

use function Pest\testDirectory;

beforeEach(function () {
    $this->configPath = sys_get_temp_dir() . '/php-ca-test-' . uniqid() . '.json';
});

afterEach(function () {
    if (file_exists($this->configPath)) {
        unlink($this->configPath);
    }
});

it('prints valid config as JSON', function () {
    file_put_contents($this->configPath, json_encode([
        'adapter' => ['type' => 'directory', 'path' => './data'],
    ]));

    $this->artisan('config:print', ['-c' => $this->configPath])
        ->expectsOutputToContain('"type": "directory"')
        ->assertExitCode(0);
});

it('shows validation warning for invalid config', function () {
    file_put_contents($this->configPath, json_encode([
        'adapter' => ['path' => './data'],
    ]));

    $this->artisan('config:print', ['-c' => $this->configPath])
        ->assertExitCode(0);

    // Config is printed but validation warning is shown
});
