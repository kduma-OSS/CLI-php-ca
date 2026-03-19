<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;

use function Laravel\Prompts\info;

class ListCommand extends BaseCommand
{
    protected $signature = 'ca:list';
    protected $description = 'List all CA certificates';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $certs = $ca->caCertificates->all();

        if (empty($certs)) {
            info('No CA certificates found.');
            return self::SUCCESS;
        }

        $activeId = $ca->state->getActiveCaCertificateId();

        $this->table(
            ['ID', 'Subject', 'Self-Signed', 'Fingerprint', 'Active'],
            array_map(fn ($c) => [
                $c->id,
                $c->getSubjectString(),
                $c->isSelfSigned ? 'Yes' : 'No',
                substr($c->fingerprint, 0, 16) . '...',
                $c->id === $activeId ? '*' : '',
            ], $certs),
        );

        return self::SUCCESS;
    }
}
