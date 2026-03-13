<?php

namespace App\Support;

use phpseclib3\File\X509;

class CertificateFingerprint
{
    public static function compute(string $pem): string
    {
        $x509 = new X509();
        $x509->loadX509($pem);
        $der = $x509->saveX509($x509->getCurrentCert(), X509::FORMAT_DER);
        $sha256 = hash('sha256', $der);
        return implode(':', str_split(strtoupper($sha256), 2));
    }
}
