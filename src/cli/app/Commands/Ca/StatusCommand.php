<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class StatusCommand extends BaseCommand
{
    protected $signature = 'ca:status';
    protected $description = 'Show CA status';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();

        $activeId = $ca->state->getActiveCaCertificateId();
        $sequence = $ca->state->getLastIssuedSequence();

        if ($activeId === null) {
            warning('No active CA certificate.');
        } else {
            $cert = $ca->caCertificates->findOrNull($activeId);

            $this->table([], [
                ['Active CA Certificate', $activeId],
                ['Subject', $cert?->getSubjectString() ?? 'N/A'],
                ['Expires', $cert?->validity->notAfter->format('Y-m-d H:i:s') ?? 'N/A'],
                ['Expired', $cert?->isExpired() ? 'Yes' : 'No'],
                ['Last Issued Sequence', $sequence],
            ]);
        }

        return self::SUCCESS;
    }
}
