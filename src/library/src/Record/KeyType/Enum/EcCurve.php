<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\KeyType\Enum;

enum EcCurve: string
{
    // SECG prime curves
    case Secp112r1 = 'secp112r1';
    case Secp112r2 = 'secp112r2';
    case Secp128r1 = 'secp128r1';
    case Secp128r2 = 'secp128r2';
    case Secp160k1 = 'secp160k1';
    case Secp160r1 = 'secp160r1';
    case Secp160r2 = 'secp160r2';
    case Secp192k1 = 'secp192k1';
    case Secp192r1 = 'secp192r1';
    case Secp224k1 = 'secp224k1';
    case Secp224r1 = 'secp224r1';
    case Secp256k1 = 'secp256k1';
    case Secp256r1 = 'secp256r1';
    case Secp384r1 = 'secp384r1';
    case Secp521r1 = 'secp521r1';

    // X9.62 prime curves
    case Prime192v2 = 'prime192v2';
    case Prime192v3 = 'prime192v3';
    case Prime239v1 = 'prime239v1';
    case Prime239v2 = 'prime239v2';
    case Prime239v3 = 'prime239v3';

    // Brainpool r1 (random) curves
    case BrainpoolP160r1 = 'brainpoolP160r1';
    case BrainpoolP192r1 = 'brainpoolP192r1';
    case BrainpoolP224r1 = 'brainpoolP224r1';
    case BrainpoolP256r1 = 'brainpoolP256r1';
    case BrainpoolP320r1 = 'brainpoolP320r1';
    case BrainpoolP384r1 = 'brainpoolP384r1';
    case BrainpoolP512r1 = 'brainpoolP512r1';

    // Brainpool t1 (twisted) curves
    case BrainpoolP160t1 = 'brainpoolP160t1';
    case BrainpoolP192t1 = 'brainpoolP192t1';
    case BrainpoolP224t1 = 'brainpoolP224t1';
    case BrainpoolP256t1 = 'brainpoolP256t1';
    case BrainpoolP320t1 = 'brainpoolP320t1';
    case BrainpoolP384t1 = 'brainpoolP384t1';
    case BrainpoolP512t1 = 'brainpoolP512t1';

    // SECG/NIST binary (sect) curves
    case Sect113r1 = 'sect113r1';
    case Sect113r2 = 'sect113r2';
    case Sect131r1 = 'sect131r1';
    case Sect131r2 = 'sect131r2';
    case Sect163k1 = 'sect163k1';
    case Sect163r1 = 'sect163r1';
    case Sect163r2 = 'sect163r2';
    case Sect193r1 = 'sect193r1';
    case Sect193r2 = 'sect193r2';
    case Sect233k1 = 'sect233k1';
    case Sect233r1 = 'sect233r1';
    case Sect239k1 = 'sect239k1';
    case Sect283k1 = 'sect283k1';
    case Sect283r1 = 'sect283r1';
    case Sect409k1 = 'sect409k1';
    case Sect409r1 = 'sect409r1';
    case Sect571k1 = 'sect571k1';
    case Sect571r1 = 'sect571r1';

    public function length(): int
    {
        return match ($this) {
            self::Secp112r1, self::Secp112r2 => 112,
            self::Sect113r1, self::Sect113r2 => 113,
            self::Secp128r1, self::Secp128r2 => 128,
            self::Sect131r1, self::Sect131r2 => 131,
            self::Secp160k1, self::Secp160r1, self::Secp160r2,
            self::BrainpoolP160r1, self::BrainpoolP160t1 => 160,
            self::Sect163k1, self::Sect163r1, self::Sect163r2 => 163,
            self::Secp192k1, self::Secp192r1,
            self::Prime192v2, self::Prime192v3,
            self::BrainpoolP192r1, self::BrainpoolP192t1 => 192,
            self::Sect193r1, self::Sect193r2 => 193,
            self::Secp224k1, self::Secp224r1,
            self::BrainpoolP224r1, self::BrainpoolP224t1 => 224,
            self::Sect233k1, self::Sect233r1 => 233,
            self::Prime239v1, self::Prime239v2, self::Prime239v3,
            self::Sect239k1 => 239,
            self::Secp256k1, self::Secp256r1,
            self::BrainpoolP256r1, self::BrainpoolP256t1 => 256,
            self::Sect283k1, self::Sect283r1 => 283,
            self::BrainpoolP320r1, self::BrainpoolP320t1 => 320,
            self::Secp384r1,
            self::BrainpoolP384r1, self::BrainpoolP384t1 => 384,
            self::Sect409k1, self::Sect409r1 => 409,
            self::BrainpoolP512r1, self::BrainpoolP512t1 => 512,
            self::Secp521r1 => 521,
            self::Sect571k1, self::Sect571r1 => 571,
        };
    }
}
