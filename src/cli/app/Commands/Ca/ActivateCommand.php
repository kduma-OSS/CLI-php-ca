<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ActivateCommand extends BaseCommand
{
    protected $signature = 'ca:activate {id}';
    protected $description = 'Set the active CA certificate';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $id = $this->argument('id');

        if (! $ca->caCertificates->has($id)) {
            error("CA certificate \"{$id}\" not found.");
            return self::FAILURE;
        }

        $ca->state->setActiveCaCertificateId($id);
        info("Active CA certificate set to \"{$id}\".");

        return self::SUCCESS;
    }
}
