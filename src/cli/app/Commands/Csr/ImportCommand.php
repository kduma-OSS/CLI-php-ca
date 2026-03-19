<?php

namespace App\Commands\Csr;

use App\Commands\BaseCommand;
use KDuma\PhpCA\Entity\CsrEntity;
use KDuma\PhpCA\Helpers\FingerprintHelper;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use phpseclib3\File\X509;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ImportCommand extends BaseCommand
{
    protected $signature = 'csr:import {pem?} {--id=}';
    protected $description = 'Import an external CSR';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();

        $path = $this->argument('pem');
        if ($path) {
            if (! file_exists($path)) {
                error("File not found: {$path}");
                return self::FAILURE;
            }
            $pem = file_get_contents($path);
        } else {
            $pem = file_get_contents('php://stdin');
        }

        if (! $pem) {
            error('No PEM data provided.');
            return self::FAILURE;
        }

        try {
            $x509 = new X509();
            $x509->loadCSR($pem);

            if (! $x509->validateSignature()) {
                error('CSR signature is invalid.');
                return self::FAILURE;
            }

            $dn = $x509->getDN(X509::DN_STRING);
            $publicKey = $x509->getPublicKey();
            $fingerprint = FingerprintHelper::compute($publicKey);

            // Find or import key
            $keyEntity = null;
            foreach ($ca->keys->all() as $key) {
                if ($key->fingerprint === $fingerprint) {
                    $keyEntity = $key;
                    break;
                }
            }

            if ($keyEntity === null) {
                // Import public key only
                $keyEntity = \KDuma\PhpCA\Entity\KeyBuilder::fromExisting($publicKey)->make();
                $ca->keys->save($keyEntity);
            }

            $entity = new CsrEntity();
            $entity->id = $this->option('id');
            $entity->subject = CertificateSubject::fromString($dn);
            $entity->keyId = $keyEntity->id;
            $entity->requestedExtensions = [];
            $entity->fingerprint = hash('sha256', $pem);
            $entity->csr = $pem;

            $ca->csrs->save($entity);
        } catch (\Throwable $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        info("CSR imported: {$entity->id}");

        return self::SUCCESS;
    }
}
