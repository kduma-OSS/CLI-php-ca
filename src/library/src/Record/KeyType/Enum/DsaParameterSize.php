<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\KeyType\Enum;

enum DsaParameterSize: string
{
    case L1024_N160 = '1024-160';
    case L2048_N224 = '2048-224';
    case L2048_N256 = '2048-256';
    case L3072_N256 = '3072-256';

    public static function fromParameters(int $L, int $N): self
    {
        return self::from("{$L}-{$N}");
    }

    public function L(): int
    {
        return match ($this) {
            self::L1024_N160 => 1024,
            self::L2048_N224, self::L2048_N256 => 2048,
            self::L3072_N256 => 3072,
        };
    }

    public function N(): int
    {
        return match ($this) {
            self::L1024_N160 => 160,
            self::L2048_N224 => 224,
            self::L2048_N256, self::L3072_N256 => 256,
        };
    }
}
