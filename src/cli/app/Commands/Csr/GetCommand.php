<?php

namespace App\Commands\Csr;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;

class GetCommand extends BaseCommand
{
    protected $signature = 'csr:get {id}';

    protected $description = 'Output CSR PEM';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $csr = $ca->csrs->findOrNull($this->argument('id'));

        if ($csr === null) {
            error('CSR not found.');

            return self::FAILURE;
        }

        $this->output->writeln($csr->csr);

        return self::SUCCESS;
    }
}
