<?php

declare(strict_types=1);

use KDuma\PhpCA\Record\KeyType\Enum\DsaParameterSize;

test('L() returns correct value for each case', function (DsaParameterSize $case, int $expectedL) {
    expect($case->L())->toBe($expectedL);
})->with([
    'L1024_N160' => [DsaParameterSize::L1024_N160, 1024],
    'L2048_N224' => [DsaParameterSize::L2048_N224, 2048],
    'L2048_N256' => [DsaParameterSize::L2048_N256, 2048],
    'L3072_N256' => [DsaParameterSize::L3072_N256, 3072],
]);

test('N() returns correct value for each case', function (DsaParameterSize $case, int $expectedN) {
    expect($case->N())->toBe($expectedN);
})->with([
    'L1024_N160' => [DsaParameterSize::L1024_N160, 160],
    'L2048_N224' => [DsaParameterSize::L2048_N224, 224],
    'L2048_N256' => [DsaParameterSize::L2048_N256, 256],
    'L3072_N256' => [DsaParameterSize::L3072_N256, 256],
]);

test('fromParameters() returns correct case for valid combinations', function (int $L, int $N, DsaParameterSize $expected) {
    expect(DsaParameterSize::fromParameters($L, $N))->toBe($expected);
})->with([
    '1024-160' => [1024, 160, DsaParameterSize::L1024_N160],
    '2048-224' => [2048, 224, DsaParameterSize::L2048_N224],
    '2048-256' => [2048, 256, DsaParameterSize::L2048_N256],
    '3072-256' => [3072, 256, DsaParameterSize::L3072_N256],
]);

test('fromParameters() throws ValueError for invalid combination', function () {
    DsaParameterSize::fromParameters(512, 128);
})->throws(ValueError::class);

test('string values match expected format', function (DsaParameterSize $case, string $expectedValue) {
    expect($case->value)->toBe($expectedValue);
})->with([
    'L1024_N160' => [DsaParameterSize::L1024_N160, '1024-160'],
    'L2048_N224' => [DsaParameterSize::L2048_N224, '2048-224'],
    'L2048_N256' => [DsaParameterSize::L2048_N256, '2048-256'],
    'L3072_N256' => [DsaParameterSize::L3072_N256, '3072-256'],
]);
