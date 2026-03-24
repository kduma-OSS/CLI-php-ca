<?php

namespace App\Commands\Certificate;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;

class ShowCommand extends BaseCommand
{
    protected $signature = 'certificate:show {id}';

    protected $description = 'Show certificate details';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $cert = $ca->certificates->findOrNull($this->argument('id'));

        if ($cert === null) {
            error('Certificate not found.');

            return self::FAILURE;
        }

        $this->table([], [
            ['ID', $cert->id],
            ['Subject', $cert->getSubjectString()],
            ['Issuer', $cert->getIssuerString()],
            ['Serial Number', $cert->serialNumber],
            ['Sequence', $cert->sequence],
            ['Not Before', $cert->validity->notBefore->format('Y-m-d H:i:s')],
            ['Not After', $cert->validity->notAfter->format('Y-m-d H:i:s')],
            ['Key ID', $cert->keyId],
            ['CA Certificate ID', $cert->caCertificateId],
            ['Template', $cert->templateId ?? '-'],
            ['Fingerprint', $cert->fingerprint],
            ['Subject Key ID', $cert->subjectKeyIdentifier],
            ['Authority Key ID', $cert->authorityKeyIdentifier],
        ]);

        return self::SUCCESS;
    }
}
