<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;

class GetCommand extends BaseCommand
{
    protected $signature = 'ca:get {id}';

    protected $description = 'Output CA certificate PEM';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $cert = $ca->caCertificates->findOrNull($this->argument('id'));

        if ($cert === null) {
            error('CA certificate not found.');

            return self::FAILURE;
        }

        $this->output->writeln($cert->certificate);

        return self::SUCCESS;
    }
}
