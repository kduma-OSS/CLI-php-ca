<?php

namespace App\Commands\Csr;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;

class ShowCommand extends BaseCommand
{
    protected $signature = 'csr:show {id}';
    protected $description = 'Show CSR details';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $csr = $ca->csrs->findOrNull($this->argument('id'));

        if ($csr === null) {
            error('CSR not found.');
            return self::FAILURE;
        }

        $this->table([], [
            ['ID', $csr->id],
            ['Subject', $csr->getSubjectString()],
            ['Key ID', $csr->keyId],
            ['Certificate ID', $csr->certificateId ?? '-'],
            ['Fingerprint', $csr->fingerprint],
            ['Extensions', count($csr->requestedExtensions)],
        ]);

        return self::SUCCESS;
    }
}
