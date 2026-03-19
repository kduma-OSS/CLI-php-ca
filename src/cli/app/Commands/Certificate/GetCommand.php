<?php

namespace App\Commands\Certificate;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;

class GetCommand extends BaseCommand
{
    protected $signature = 'certificate:get {id}';
    protected $description = 'Output certificate PEM';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $cert = $ca->certificates->findOrNull($this->argument('id'));

        if ($cert === null) {
            error('Certificate not found.');
            return self::FAILURE;
        }

        $this->output->writeln($cert->certificate);

        return self::SUCCESS;
    }
}
