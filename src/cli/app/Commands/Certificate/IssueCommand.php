<?php

namespace App\Commands\Certificate;

use App\Commands\BaseCommand;
use App\Concerns\LaravelPromptsInputProvider;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class IssueCommand extends BaseCommand
{
    protected $signature = 'certificate:issue {--template=} {--key=} {--dn=} {--ca-cert=} {--ca-key=} {--var=*}';

    protected $description = 'Issue a certificate from a template and key';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();

        $templateId = $this->option('template') ?? text('Template ID', required: true);
        $keyId = $this->option('key') ?? text('Key ID', required: true);
        $dn = $this->option('dn') ?? text('Distinguished Name (e.g. CN=example.com)', required: true);

        $caCertId = $this->option('ca-cert') ?? $ca->state->getActiveCaCertificateId();
        if ($caCertId === null) {
            error('No active CA certificate. Specify --ca-cert or activate one with ca:activate.');

            return self::FAILURE;
        }

        $caCert = $ca->caCertificates->findOrNull($caCertId);
        if ($caCert === null) {
            error("CA certificate \"{$caCertId}\" not found.");

            return self::FAILURE;
        }

        $caKeyId = $this->option('ca-key') ?? $caCert->keyId;

        // Parse --var options into presets
        $presets = $this->parseVarOptions();
        $inputProvider = new LaravelPromptsInputProvider($presets);

        try {
            $cert = $ca->certificates->getBuilder()
                ->template($templateId)
                ->key($keyId)
                ->subject(CertificateSubject::fromString($dn))
                ->signedBy($caCertId, $caKeyId)
                ->inputProvider($inputProvider)
                ->save();
        } catch (\Throwable $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        info("Certificate issued: {$cert->id}");
        $this->output->writeln($cert->id);

        return self::SUCCESS;
    }

    /**
     * Parse --var "alias=value" options into a presets array.
     * Repeated aliases accumulate into arrays.
     *
     * @return array<string, string|string[]>
     */
    private function parseVarOptions(): array
    {
        $presets = [];

        foreach ($this->option('var') as $var) {
            $pos = strpos($var, '=');
            if ($pos === false) {
                continue;
            }

            $alias = substr($var, 0, $pos);
            $value = substr($var, $pos + 1);

            if (! isset($presets[$alias])) {
                $presets[$alias] = $value;
            } else {
                // Convert to array on second occurrence
                if (! is_array($presets[$alias])) {
                    $presets[$alias] = [$presets[$alias]];
                }
                $presets[$alias][] = $value;
            }
        }

        return $presets;
    }
}
