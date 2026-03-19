<?php

namespace App\Commands\Ca\Csr;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;

class GetCommand extends BaseCommand
{
    protected $signature = 'ca:csr:get {id}';
    protected $description = 'Output CA CSR PEM';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $csr = $ca->caCsrs->findOrNull($this->argument('id'));

        if ($csr === null) {
            error('CA CSR not found.');
            return self::FAILURE;
        }

        $this->output->writeln($csr->csr);

        return self::SUCCESS;
    }
}
