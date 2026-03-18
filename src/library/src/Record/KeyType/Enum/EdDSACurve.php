<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\KeyType\Enum;

enum EdDSACurve: string
{
    case Ed25519 = 'Ed25519';
    case Ed448 = 'Ed448';

    public function length(): int
    {
        return match ($this) {
            self::Ed25519 => 256,
            self::Ed448 => 448,
        };
    }
}
