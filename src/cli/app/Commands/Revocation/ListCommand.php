<?php

namespace App\Commands\Revocation;

use App\Commands\BaseCommand;

use function Laravel\Prompts\info;

class ListCommand extends BaseCommand
{
    protected $signature = 'revocation:list';
    protected $description = 'List all revocations';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $revocations = $ca->revocations->all();

        if (empty($revocations)) {
            info('No revocations found.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Certificate ID', 'Serial', 'Reason', 'Revoked At'],
            array_map(fn ($r) => [
                $r->id,
                $r->certificateId,
                $r->serialNumber,
                $r->reason->value,
                $r->revokedAt->format('Y-m-d H:i:s'),
            ], $revocations),
        );

        return self::SUCCESS;
    }
}
