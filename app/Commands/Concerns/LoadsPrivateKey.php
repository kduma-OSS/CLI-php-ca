<?php

namespace App\Commands\Concerns;

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey as RSAPrivateKey;

use function Laravel\Prompts\password;

trait LoadsPrivateKey
{
    protected function loadPrivateKey(string $pem, ?string &$password = null): RSAPrivateKey
    {
        $password = $this->option('password') ?? false;

        try {
            return RSA::loadPrivateKey($pem, $password);
        } catch (\Exception $e) {
            if ($password !== false) {
                throw $e;
            }

            $password = password('Enter password for private key', required: true);

            return RSA::loadPrivateKey($pem, $password);
        }
    }
}
