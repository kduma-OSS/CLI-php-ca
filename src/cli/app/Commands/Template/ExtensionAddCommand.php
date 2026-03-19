<?php

namespace App\Commands\Template;

use App\Commands\BaseCommand;
use KDuma\PhpCA\Record\Extension\Resolver\InputMultipleResolver;
use KDuma\PhpCA\Record\Extension\Resolver\InputResolver;
use KDuma\PhpCA\Record\Extension\Resolver\LiteralResolver;
use KDuma\PhpCA\Record\Extension\Resolver\SubjectKeyFingerprintResolver;
use KDuma\PhpCA\Record\Extension\Resolver\CaKeyFingerprintResolver;
use KDuma\PhpCA\Record\Extension\Resolver\TemplateStringResolver;
use KDuma\PhpCA\Record\Extension\Resolver\RelativeDateResolver;
use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\AuthorityInfoAccessExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\AuthorityKeyIdentifierExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\BasicConstraintsExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\CertificatePoliciesExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\CrlDistributionPointsExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\ExtKeyUsageExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\KeyUsageExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\NetscapeCommentExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\PrivateKeyUsagePeriodExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\SubjectAltNameExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\SubjectKeyIdentifierExtensionTemplate;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class ExtensionAddCommand extends BaseCommand
{
    protected $signature = 'template:extension:add {id} {extension-type?} {--json=}';
    protected $description = 'Add an extension to a template';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $template = $ca->templates->findOrNull($this->argument('id'));

        if ($template === null) {
            error('Template not found.');
            return self::FAILURE;
        }

        $types = ExtensionRegistry::getTemplateTypes();
        $typeNames = array_keys($types);

        $extensionType = $this->argument('extension-type')
            ?? select('Extension type', $typeNames);

        if (! isset($types[$extensionType])) {
            error("Unknown extension type: {$extensionType}");
            return self::FAILURE;
        }

        // Check if already exists
        foreach ($template->extensions as $ext) {
            if ($ext::name() === $extensionType) {
                error("Extension \"{$extensionType}\" already exists on this template.");
                return self::FAILURE;
            }
        }

        // JSON mode — non-interactive
        if ($this->option('json')) {
            $data = json_decode($this->option('json'), true);
            if (! is_array($data)) {
                error('Invalid JSON.');
                return self::FAILURE;
            }
            $data['name'] = $extensionType;
            $extension = $types[$extensionType]::fromArray($data);
        } else {
            $extension = $this->buildExtensionInteractively($extensionType);
        }

        if ($extension === null) {
            error('Failed to configure extension.');
            return self::FAILURE;
        }

        $template->extensions[] = $extension;
        $ca->templates->save($template);

        info("Extension \"{$extensionType}\" added to template \"{$template->id}\".");

        return self::SUCCESS;
    }

    private function buildExtensionInteractively(string $type): ?BaseExtensionTemplate
    {
        return match ($type) {
            'basic-constraints' => new BasicConstraintsExtensionTemplate(
                ca: confirm('CA certificate?', default: false),
                pathLenConstraint: ($v = text('Path length constraint (empty for none)')) !== '' ? (int) $v : null,
                critical: confirm('Critical?', default: true),
            ),
            'key-usage' => new KeyUsageExtensionTemplate(
                digitalSignature: confirm('Digital Signature?', default: false),
                nonRepudiation: confirm('Non-Repudiation?', default: false),
                keyEncipherment: confirm('Key Encipherment?', default: false),
                dataEncipherment: confirm('Data Encipherment?', default: false),
                keyAgreement: confirm('Key Agreement?', default: false),
                keyCertSign: confirm('Key Cert Sign?', default: false),
                cRLSign: confirm('CRL Sign?', default: false),
                encipherOnly: confirm('Encipher Only?', default: false),
                decipherOnly: confirm('Decipher Only?', default: false),
                critical: confirm('Critical?', default: true),
            ),
            'ext-key-usage' => new ExtKeyUsageExtensionTemplate(
                usages: multiselect(
                    'Extended key usages',
                    ['serverAuth', 'clientAuth', 'codeSigning', 'emailProtection', 'timeStamping', 'OCSPSigning'],
                ),
                critical: confirm('Critical?', default: false),
            ),
            'subject-alt-name' => $this->buildSubjectAltNameInteractively(),
            'crl-distribution-points' => $this->buildCrlDistPointsInteractively(),
            'authority-info-access' => $this->buildAiaInteractively(),
            'private-key-usage-period' => new PrivateKeyUsagePeriodExtensionTemplate(
                notBefore: new RelativeDateResolver(base: 'not-before'),
                notAfter: new RelativeDateResolver(base: 'not-after'),
                critical: confirm('Critical?', default: false),
            ),
            'netscape-comment' => $this->buildNetscapeCommentInteractively(),
            'certificate-policies' => $this->buildCertificatePoliciesInteractively(),
            default => $this->buildFromJson($type),
        };
    }

    private function buildSubjectAltNameInteractively(): SubjectAltNameExtensionTemplate
    {
        $fields = [];
        $fieldDefs = [
            'dns-names' => ['DNS names', 'dnsNames'],
            'ip-addresses' => ['IP addresses', 'ipAddresses'],
            'emails' => ['Email addresses', 'emails'],
            'uris' => ['URIs', 'uris'],
        ];

        foreach ($fieldDefs as $alias => [$label, $prop]) {
            $mode = select("{$label} — how to provide?", [
                'skip' => 'Skip (empty)',
                'literal' => 'Fixed values',
                'input' => 'Ask at issuance time',
            ], default: 'skip');

            $fields[$prop] = match ($mode) {
                'literal' => array_filter(array_map('trim', explode(',', text("{$label} (comma-separated)")))),
                'input' => new InputMultipleResolver(alias: $alias, label: $label),
                default => [],
            };
        }

        return new SubjectAltNameExtensionTemplate(
            dnsNames: $fields['dnsNames'],
            ipAddresses: $fields['ipAddresses'],
            emails: $fields['emails'],
            uris: $fields['uris'],
            critical: confirm('Critical?', default: false),
        );
    }

    private function buildCrlDistPointsInteractively(): CrlDistributionPointsExtensionTemplate
    {
        $mode = select('CRL URIs — how to provide?', [
            'literal' => 'Fixed URIs',
            'template' => 'URI template with {variables}',
            'input' => 'Ask at issuance time',
        ]);

        $uris = match ($mode) {
            'literal' => array_filter(array_map('trim', explode(',', text('CRL URIs (comma-separated)', required: true)))),
            'template' => [new TemplateStringResolver(text('URI template (e.g. http://crl.example.com/{ca-fingerprint}.crl)', required: true))],
            'input' => [new InputMultipleResolver(alias: 'crl-uris', label: 'CRL URIs')],
        };

        return new CrlDistributionPointsExtensionTemplate(
            uris: $uris,
            critical: confirm('Critical?', default: false),
        );
    }

    private function buildAiaInteractively(): AuthorityInfoAccessExtensionTemplate
    {
        $ocsp = [];
        $caIssuers = [];

        if (confirm('Configure OCSP URIs?', default: false)) {
            $mode = select('OCSP URIs — how?', ['literal' => 'Fixed', 'template' => 'Template', 'input' => 'Ask at issuance']);
            $ocsp = match ($mode) {
                'literal' => array_filter(array_map('trim', explode(',', text('OCSP URIs (comma-separated)')))),
                'template' => [new TemplateStringResolver(text('OCSP URI template', required: true))],
                'input' => [new InputMultipleResolver(alias: 'ocsp-uris', label: 'OCSP URIs')],
            };
        }

        if (confirm('Configure CA Issuers URIs?', default: false)) {
            $mode = select('CA Issuers URIs — how?', ['literal' => 'Fixed', 'template' => 'Template', 'input' => 'Ask at issuance']);
            $caIssuers = match ($mode) {
                'literal' => array_filter(array_map('trim', explode(',', text('CA Issuers URIs (comma-separated)')))),
                'template' => [new TemplateStringResolver(text('CA Issuers URI template', required: true))],
                'input' => [new InputMultipleResolver(alias: 'ca-issuers-uris', label: 'CA Issuers URIs')],
            };
        }

        return new AuthorityInfoAccessExtensionTemplate(
            ocspUris: $ocsp,
            caIssuersUris: $caIssuers,
            critical: confirm('Critical?', default: false),
        );
    }

    private function buildNetscapeCommentInteractively(): NetscapeCommentExtensionTemplate
    {
        $mode = select('Comment — how to provide?', [
            'literal' => 'Fixed text',
            'template' => 'Template with {variables}',
            'input' => 'Ask at issuance time',
        ]);

        $comment = match ($mode) {
            'literal' => new LiteralResolver(text('Comment', required: true)),
            'template' => new TemplateStringResolver(text('Comment template (e.g. "Issued by {ca-subject-cn}")', required: true)),
            'input' => new InputResolver(alias: 'comment', label: 'Certificate comment'),
        };

        return new NetscapeCommentExtensionTemplate(
            comment: $comment,
            critical: confirm('Critical?', default: false),
        );
    }

    private function buildCertificatePoliciesInteractively(): CertificatePoliciesExtensionTemplate
    {
        $policies = [];

        do {
            $oid = text('Policy OID (e.g. 2.5.29.32.0 or 1.2.3.4.5)', required: true);
            $policy = ['oid' => $oid];

            if (confirm('Add CPS URI?', default: false)) {
                $mode = select('CPS URI — how to provide?', [
                    'literal' => 'Fixed URI',
                    'template' => 'URI template with {variables}',
                    'input' => 'Ask at issuance time',
                ]);

                $policy['cps'] = match ($mode) {
                    'literal' => text('CPS URI', required: true),
                    'template' => new TemplateStringResolver(text('CPS URI template', required: true)),
                    'input' => new InputResolver(alias: 'cps-uri', label: 'CPS URI'),
                };
            }

            if (confirm('Add user notice text?', default: false)) {
                $mode = select('Notice text — how to provide?', [
                    'literal' => 'Fixed text',
                    'template' => 'Template with {variables}',
                    'input' => 'Ask at issuance time',
                ]);

                $policy['notice'] = match ($mode) {
                    'literal' => text('Notice text', required: true),
                    'template' => new TemplateStringResolver(text('Notice template', required: true)),
                    'input' => new InputResolver(alias: 'notice-text', label: 'Notice text'),
                };
            }

            if (confirm('Add notice reference?', default: false)) {
                $orgMode = select('Organization — how to provide?', [
                    'literal' => 'Fixed text',
                    'template' => 'Template with {variables}',
                    'input' => 'Ask at issuance time',
                ]);

                $organization = match ($orgMode) {
                    'literal' => text('Organization name', required: true),
                    'template' => new TemplateStringResolver(text('Organization template', required: true)),
                    'input' => new InputResolver(alias: 'notice-ref-org', label: 'Notice reference organization'),
                };

                $noticeNumbers = array_map(
                    'intval',
                    array_filter(array_map('trim', explode(',', text('Notice numbers (comma-separated integers)', required: true)))),
                );

                $policy['noticeRef'] = [
                    'organization' => $organization,
                    'noticeNumbers' => $noticeNumbers,
                ];
            }

            $policies[] = $policy;
        } while (confirm('Add another policy?', default: false));

        return new CertificatePoliciesExtensionTemplate(
            policies: $policies,
            critical: confirm('Critical?', default: false),
        );
    }

    private function buildFromJson(string $type): ?BaseExtensionTemplate
    {
        $json = text("Extension \"{$type}\" config (JSON)", required: true);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return null;
        }

        $data['name'] = $type;
        $class = ExtensionRegistry::resolveTemplate($type);

        return $class::fromArray($data);
    }
}
