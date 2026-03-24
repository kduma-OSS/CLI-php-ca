<?php

namespace App\Commands\Ca\Csr;

use App\Commands\BaseCommand;

use function Laravel\Prompts\info;

class ListCommand extends BaseCommand
{
    protected $signature = 'ca:csr:list';

    protected $description = 'List all CA CSRs';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $csrs = $ca->caCsrs->all();

        if (empty($csrs)) {
            info('No CA CSRs found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Subject', 'Key ID', 'CA Cert ID'],
            array_map(fn ($c) => [
                $c->id,
                $c->getSubjectString(),
                $c->keyId,
                $c->caCertificateId ?? '-',
            ], $csrs),
        );

        return self::SUCCESS;
    }
}
