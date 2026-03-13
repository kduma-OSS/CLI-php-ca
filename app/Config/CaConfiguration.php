<?php

namespace App\Config;

use App\Storage\Database;

readonly class CaConfiguration
{
    /**
     * @param  array<string, CertificateTemplateConfig>  $certificateTemplates
     * @param  array<EncryptionRuleConfig>|null  $encryption
     */
    public function __construct(
        public string $basePath,
        public string $databasePath,
        public CertificationAuthorityConfig $certificationAuthority,
        public array $certificateTemplates,
        public ?array $encryption = null,
    ) {}

    public static function fromArray(string $basePath, array $data): self
    {
        $templates = [];

        foreach ($data['certificate_templates'] ?? [] as $name => $template) {
            $templates[$name] = CertificateTemplateConfig::fromArray($template);
        }

        $encryption = null;

        if (isset($data['encryption'])) {
            $encryption = array_map(
                fn (array $rule) => EncryptionRuleConfig::fromArray($rule),
                $data['encryption'],
            );
        }

        return new self(
            basePath: $basePath,
            databasePath: $data['database_path'],
            certificationAuthority: CertificationAuthorityConfig::fromArray($data['certification_authority']),
            certificateTemplates: $templates,
            encryption: $encryption,
        );
    }

    public function database(): Database
    {
        $path = $this->databasePath;

        if (! str_starts_with($path, '/')) {
            $path = $this->basePath.'/'.$path;
        }

        return new Database($path);
    }
}
