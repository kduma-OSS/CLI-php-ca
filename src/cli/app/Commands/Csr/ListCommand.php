<?php

namespace App\Commands\Csr;

use App\Commands\BaseCommand;

use function Laravel\Prompts\info;

class ListCommand extends BaseCommand
{
    protected $signature = 'csr:list';
    protected $description = 'List all CSRs';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $csrs = $ca->csrs->all();

        if (empty($csrs)) {
            info('No CSRs found.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Subject', 'Key ID', 'Certificate ID'],
            array_map(fn ($c) => [
                $c->id,
                $c->getSubjectString(),
                $c->keyId,
                $c->certificateId ?? '-',
            ], $csrs),
        );

        return self::SUCCESS;
    }
}
