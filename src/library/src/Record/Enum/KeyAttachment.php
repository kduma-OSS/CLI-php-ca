<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Enum;

enum KeyAttachment: string
{
    case PrivateKey = 'private_key.pem';
    case PublicKey = 'public_key.pem';
}
