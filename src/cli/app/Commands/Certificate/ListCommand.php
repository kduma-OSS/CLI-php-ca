<?php

namespace App\Commands\Certificate;

use App\Commands\BaseCommand;

use function Laravel\Prompts\info;

class ListCommand extends BaseCommand
{
    protected $signature = 'certificate:list';

    protected $description = 'List all issued certificates';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $certs = $ca->certificates->all();

        if (empty($certs)) {
            info('No certificates found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Subject', 'Serial', 'Template', 'Not After'],
            array_map(fn ($c) => [
                $c->id,
                $c->getSubjectString(),
                substr($c->serialNumber, 0, 16).'...',
                $c->templateId ?? '-',
                $c->validity->notAfter->format('Y-m-d'),
            ], $certs),
        );

        return self::SUCCESS;
    }
}
