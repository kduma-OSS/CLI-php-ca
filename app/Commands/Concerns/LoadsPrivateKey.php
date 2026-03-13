<?php

namespace App\Commands\Concerns;

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey as RSAPrivateKey;

trait LoadsPrivateKey
{
    protected function loadPrivateKey(string $pem): RSAPrivateKey
    {
        $password = $this->option('password') ?? false;

        try {
            return RSA::loadPrivateKey($pem, $password);
        } catch (\Exception $e) {
            if ($password !== false) {
                throw $e;
            }

            $password = $this->secret('Enter password for private key');
            if (!$password) {
                throw new \RuntimeException('Password cannot be empty');
            }

            return RSA::loadPrivateKey($pem, $password);
        }
    }
}
