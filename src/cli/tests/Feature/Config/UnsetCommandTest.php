<?php

declare(strict_types=1);
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->configPath = sys_get_temp_dir().'/php-ca-test-'.uniqid().'.json';
    file_put_contents($this->configPath, json_encode([
        'adapter' => ['type' => 'directory', 'path' => './data'],
        'integrity' => ['hasher' => ['type' => 'sha256']],
    ]));
});

afterEach(function () {
    if (file_exists($this->configPath)) {
        unlink($this->configPath);
    }
});

it('removes existing key', function () {
    $this->artisan('config:unset', ['key' => 'integrity', '-c' => $this->configPath])
        ->assertExitCode(0);

    $data = json_decode(file_get_contents($this->configPath), true);
    expect($data)->not->toHaveKey('integrity');
});

it('fails on missing key', function () {
    $this->artisan('config:unset', ['key' => 'nonexistent', '-c' => $this->configPath])
        ->assertExitCode(1);
});
