<?php

namespace App\Config\Enums;

enum EncryptionSourceType: string
{
    case Env = 'env';
    case File = 'file';
}
