<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ImportCommand extends BaseCommand
{
    protected $signature = 'ca:import {pem?} {--id=} {--key=} {--activate}';
    protected $description = 'Import an existing CA certificate';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();

        $path = $this->argument('pem');
        if ($path) {
            if (! file_exists($path)) {
                error("File not found: {$path}");
                return self::FAILURE;
            }
            $pem = file_get_contents($path);
        } else {
            $pem = file_get_contents('php://stdin');
        }

        if (! $pem) {
            error('No PEM data provided.');
            return self::FAILURE;
        }

        try {
            $builder = $ca->caCertificates->getBuilder()->import($pem);

            if ($this->option('id')) {
                $builder->id($this->option('id'));
            }

            if ($this->option('key')) {
                $builder->key($this->option('key'));
            }

            $cert = $builder->save();
        } catch (\Throwable $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        info("CA certificate imported: {$cert->id}");

        if ($this->option('activate')) {
            $ca->state->setActiveCaCertificateId($cert->id);
            info('Set as active CA certificate.');
        }

        return self::SUCCESS;
    }
}
