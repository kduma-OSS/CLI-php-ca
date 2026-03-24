<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;

class ShowCommand extends BaseCommand
{
    protected $signature = 'ca:show {id}';

    protected $description = 'Show CA certificate details';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $cert = $ca->caCertificates->findOrNull($this->argument('id'));

        if ($cert === null) {
            error('CA certificate not found.');

            return self::FAILURE;
        }

        $activeId = $ca->state->getActiveCaCertificateId();

        $this->table([], [
            ['ID', $cert->id],
            ['Subject', $cert->getSubjectString()],
            ['Issuer', $cert->getIssuerString()],
            ['Serial Number', $cert->serialNumber],
            ['Not Before', $cert->validity->notBefore->format('Y-m-d H:i:s')],
            ['Not After', $cert->validity->notAfter->format('Y-m-d H:i:s')],
            ['Expired', $cert->isExpired() ? 'Yes' : 'No'],
            ['Self-Signed', $cert->isSelfSigned ? 'Yes' : 'No'],
            ['Key ID', $cert->keyId],
            ['Subject Key ID', $cert->subjectKeyIdentifier],
            ['Authority Key ID', $cert->authorityKeyIdentifier],
            ['Fingerprint', $cert->fingerprint],
            ['Active', $cert->id === $activeId ? 'Yes' : 'No'],
        ]);

        return self::SUCCESS;
    }
}
