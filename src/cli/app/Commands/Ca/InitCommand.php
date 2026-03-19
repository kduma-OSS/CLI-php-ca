<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InitCommand extends BaseCommand
{
    protected $signature = 'ca:init
        {--key-type=rsa : Key type (rsa, ecdsa, eddsa)}
        {--key-size=4096 : RSA key size}
        {--key-curve= : ECDSA/EdDSA curve}
        {--dn= : Distinguished Name}
        {--validity=P20Y : CA certificate validity}
        {--root-ca : Create self-signed root CA instead of CSR}
        {--crl-url= : CRL distribution point URL (supports {ca-fingerprint})}
        {--aia-url= : AIA / CA Issuers URL (supports {ca-fingerprint})}
        {--non-interactive : Skip all prompts, use defaults and options}';

    protected $description = 'Initialize a new Certificate Authority';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();

        // Pre-flight checks
        if (count($ca->caCertificates->all()) > 0) {
            error('CA already has certificates. Cannot re-initialize.');
            return self::FAILURE;
        }

        if (count($ca->templates->all()) > 0) {
            error('CA already has templates. Cannot re-initialize.');
            return self::FAILURE;
        }

        if ($ca->keys->has('ca')) {
            error('Key "ca" already exists. Cannot re-initialize.');
            return self::FAILURE;
        }

        // Collect parameters
        $interactive = ! $this->option('non-interactive');

        $keyType = $this->option('key-type');
        if ($interactive && ! $this->option('key-type')) {
            $keyType = select('Key type', ['rsa', 'ecdsa', 'eddsa'], default: 'rsa');
        }

        $dn = $this->option('dn');
        if (! $dn && $interactive) {
            $dn = text('Distinguished Name (e.g. CN=My Root CA, O=MyOrg, C=US)', required: true);
        }
        if (! $dn) {
            error('Distinguished Name is required (--dn).');
            return self::FAILURE;
        }

        $rootCa = $this->option('root-ca');
        if ($interactive && ! $rootCa) {
            $rootCa = confirm('Create self-signed root CA? (No = create CSR for signing by parent CA)', default: false);
        }

        $validity = $this->option('validity');

        $crlUrl = $this->option('crl-url');
        if ($interactive && ! $crlUrl) {
            $crlUrl = text('CRL distribution point URL (empty to skip)', default: '');
        }

        $aiaUrl = $this->option('aia-url');
        if ($interactive && ! $aiaUrl) {
            $aiaUrl = text('CA Issuers (AIA) URL (empty to skip)', default: '');
        }

        // Step 1: Generate CA key
        info('Generating CA key...');
        $keyResult = match ($keyType) {
            'rsa' => $this->call('key:create:rsa', array_filter([
                '--id' => 'ca',
                '--size' => $this->option('key-size'),
                '-c' => $this->option('ca-config-file'),
            ])),
            'ecdsa' => $this->call('key:create:ecdsa', array_filter([
                '--id' => 'ca',
                '--curve' => $this->option('key-curve'),
                '-c' => $this->option('ca-config-file'),
            ])),
            'eddsa' => $this->call('key:create:eddsa', array_filter([
                '--id' => 'ca',
                '--curve' => $this->option('key-curve'),
                '-c' => $this->option('ca-config-file'),
            ])),
            default => self::FAILURE,
        };

        if ($keyResult !== self::SUCCESS) {
            error('Failed to create CA key.');
            return self::FAILURE;
        }

        $configFile = $this->option('ca-config-file');
        $baseArgs = $configFile ? ['-c' => $configFile] : [];

        // Step 2: Create templates
        info('Creating default templates...');

        // base template
        $this->call('template:create', array_merge($baseArgs, [
            'id' => 'base',
            '--display-name' => 'Base Template',
            '--validity' => $validity,
        ]));

        if ($crlUrl) {
            $this->addExtensionJson('base', 'crl-distribution-points', json_encode([
                'uris' => [['type' => 'template', 'template' => $crlUrl]],
            ]));
        }

        if ($aiaUrl) {
            $this->addExtensionJson('base', 'authority-info-access', json_encode([
                'ca_issuers_uris' => [['type' => 'template', 'template' => $aiaUrl]],
            ]));
        }

        // subordinate-ca
        $this->call('template:create', array_merge($baseArgs, [
            'id' => 'subordinate-ca',
            '--display-name' => 'Subordinate CA',
            '--parent' => 'base',
            '--validity' => 'P10Y',
        ]));

        $this->addExtensionJson('subordinate-ca', 'basic-constraints', '{"ca":true,"critical":true}');
        $this->addExtensionJson('subordinate-ca', 'key-usage', '{"key_cert_sign":true,"crl_sign":true,"critical":true}');

        // intermediate-ca
        $this->call('template:create', array_merge($baseArgs, [
            'id' => 'intermediate-ca',
            '--display-name' => 'Intermediate CA (pathLen=0)',
            '--parent' => 'subordinate-ca',
            '--validity' => 'P5Y',
        ]));

        $this->addExtensionJson('intermediate-ca', 'basic-constraints', '{"ca":true,"path_len_constraint":0,"critical":true}');

        // base-end-entity
        $this->call('template:create', array_merge($baseArgs, [
            'id' => 'base-end-entity',
            '--display-name' => 'Base End Entity',
            '--parent' => 'base',
        ]));

        $this->addExtensionJson('base-end-entity', 'basic-constraints', '{"ca":false,"critical":true}');

        // web-server
        $this->call('template:create', array_merge($baseArgs, [
            'id' => 'web-server',
            '--display-name' => 'Web Server Certificate (1 year)',
            '--parent' => 'base-end-entity',
            '--validity' => 'P1Y',
        ]));

        $this->addExtensionJson('web-server', 'key-usage', '{"digital_signature":true,"key_encipherment":true,"critical":true}');
        $this->addExtensionJson('web-server', 'ext-key-usage', '{"usages":["serverAuth"]}');
        $this->addExtensionJson('web-server', 'subject-alt-name', json_encode([
            'dns_names' => ['type' => 'input-multiple', 'alias' => 'dns-names', 'label' => 'DNS names'],
        ]));

        // crl-signer
        $this->call('template:create', array_merge($baseArgs, [
            'id' => 'crl-signer',
            '--display-name' => 'CRL Signing Certificate',
            '--parent' => 'base-end-entity',
            '--validity' => 'P2Y',
        ]));

        $this->addExtensionJson('crl-signer', 'key-usage', '{"crl_sign":true,"critical":true}');

        // Step 3: Create CA certificate or CSR
        if ($rootCa) {
            info('Creating self-signed CA certificate...');
            $this->call('ca:self-sign', array_merge($baseArgs, [
                '--id' => 'ca',
                '--key' => 'ca',
                '--dn' => $dn,
                '--validity' => $validity,
                '--activate' => true,
            ]));

            $this->newLine();
            info('CA initialized. You can now issue certificates:');
            info('  php-ca certificate:issue --template=web-server --key=<key-id> --dn="CN=example.com"');
        } else {
            info('Creating CA CSR...');
            $this->call('ca:csr:create', array_merge($baseArgs, [
                '--id' => 'ca',
                '--key' => 'ca',
                '--dn' => $dn,
            ]));

            $this->newLine();
            info('CA initialized in CSR mode. Next steps:');
            info('  1. Get the CSR signed: php-ca ca:csr:get ca');
            info('  2. Import the signed certificate: php-ca ca:import <cert.pem> --activate');
        }

        // Summary
        $this->newLine();
        $this->table([], [
            ['Key ID', 'ca'],
            ['Templates', 'base, subordinate-ca, intermediate-ca, base-end-entity, web-server, crl-signer'],
            ['Mode', $rootCa ? 'Self-signed root CA' : 'CSR (awaiting signing)'],
        ]);

        return self::SUCCESS;
    }

    private function addExtensionJson(string $templateId, string $extensionType, string $json): void
    {
        $configFile = $this->option('ca-config-file');
        $baseArgs = $configFile ? ['-c' => $configFile] : [];

        $this->call('template:extension:add', array_merge($baseArgs, [
            'id' => $templateId,
            'extension-type' => $extensionType,
            '--json' => $json,
        ]));
    }
}
