<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\Converter\DateIntervalConverter;

test('fromStorage/toStorage round-trip for P1Y', function () {
    $converter = new DateIntervalConverter;

    $interval = $converter->fromStorage('P1Y');

    expect($interval)->toBeInstanceOf(DateInterval::class)
        ->and($interval->y)->toBe(1)
        ->and($interval->m)->toBe(0)
        ->and($interval->d)->toBe(0);

    expect($converter->toStorage($interval))->toBe('P1Y');
});

test('fromStorage/toStorage round-trip for P30D', function () {
    $converter = new DateIntervalConverter;

    $interval = $converter->fromStorage('P30D');

    expect($interval)->toBeInstanceOf(DateInterval::class)
        ->and($interval->d)->toBe(30);

    expect($converter->toStorage($interval))->toBe('P30D');
});

test('fromStorage/toStorage round-trip for P1Y6M', function () {
    $converter = new DateIntervalConverter;

    $interval = $converter->fromStorage('P1Y6M');

    expect($interval)->toBeInstanceOf(DateInterval::class)
        ->and($interval->y)->toBe(1)
        ->and($interval->m)->toBe(6);

    expect($converter->toStorage($interval))->toBe('P1Y6M');
});

test('fromStorage/toStorage round-trip for P2Y3M15D', function () {
    $converter = new DateIntervalConverter;

    $interval = $converter->fromStorage('P2Y3M15D');

    expect($interval)->toBeInstanceOf(DateInterval::class)
        ->and($interval->y)->toBe(2)
        ->and($interval->m)->toBe(3)
        ->and($interval->d)->toBe(15);

    expect($converter->toStorage($interval))->toBe('P2Y3M15D');
});

test('fromStorage/toStorage round-trip with time component', function () {
    $converter = new DateIntervalConverter;

    $interval = $converter->fromStorage('PT12H');

    expect($interval)->toBeInstanceOf(DateInterval::class)
        ->and($interval->h)->toBe(12);

    expect($converter->toStorage($interval))->toBe('PT12H');
});

test('fromStorage() returns null for non-string input', function () {
    $converter = new DateIntervalConverter;

    expect($converter->fromStorage(null))->toBeNull()
        ->and($converter->fromStorage(123))->toBeNull()
        ->and($converter->fromStorage([]))->toBeNull();
});

test('toStorage() returns value unchanged if not a DateInterval', function () {
    $converter = new DateIntervalConverter;

    expect($converter->toStorage(null))->toBeNull()
        ->and($converter->toStorage('P1Y'))->toBe('P1Y');
});

test('toStorage() returns P0D for zero-length interval', function () {
    $converter = new DateIntervalConverter;
    $interval = new DateInterval('P0D');

    expect($converter->toStorage($interval))->toBe('P0D');
});

test('fromStorage/toStorage round-trip for P1Y2M3DT4H5M6S', function () {
    $converter = new DateIntervalConverter;

    $interval = $converter->fromStorage('P1Y2M3DT4H5M6S');

    expect($interval->y)->toBe(1)
        ->and($interval->m)->toBe(2)
        ->and($interval->d)->toBe(3)
        ->and($interval->h)->toBe(4)
        ->and($interval->i)->toBe(5)
        ->and($interval->s)->toBe(6);

    expect($converter->toStorage($interval))->toBe('P1Y2M3DT4H5M6S');
});
