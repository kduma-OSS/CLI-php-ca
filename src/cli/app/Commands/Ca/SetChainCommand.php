<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class SetChainCommand extends BaseCommand
{
    protected $signature = 'ca:set-chain {id} {chain? : Path to chain PEM file (reads stdin if omitted)} {--clear : Remove the chain}';
    protected $description = 'Set or clear the upstream CA chain on a CA certificate';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $cert = $ca->caCertificates->findOrNull($this->argument('id'));

        if ($cert === null) {
            error('CA certificate not found.');
            return self::FAILURE;
        }

        if ($this->option('clear')) {
            $cert->chain = null;
            $ca->caCertificates->save($cert);
            info('Chain removed from CA certificate.');
            return self::SUCCESS;
        }

        $path = $this->argument('chain');
        if ($path) {
            if (! file_exists($path)) {
                error("File not found: {$path}");
                return self::FAILURE;
            }
            $chainPem = file_get_contents($path);
        } else {
            $chainPem = file_get_contents('php://stdin');
        }

        if (! $chainPem || ! str_contains($chainPem, '-----BEGIN CERTIFICATE-----')) {
            error('No valid PEM certificate data provided.');
            return self::FAILURE;
        }

        $cert->chain = $chainPem;
        $ca->caCertificates->save($cert);
        info('Chain set on CA certificate.');

        return self::SUCCESS;
    }
}
