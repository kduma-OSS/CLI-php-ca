<?php

namespace App\Config;

use App\Storage\Database;

readonly class CaConfiguration
{
    /**
     * @param  array<string, CertificateTemplateConfig>  $certificateTemplates
     */
    public function __construct(
        public string $basePath,
        public string $databasePath,
        public CertificationAuthorityConfig $certificationAuthority,
        public array $certificateTemplates,
    ) {}

    public static function fromArray(string $basePath, array $data): self
    {
        $templates = [];

        foreach ($data['certificate_templates'] ?? [] as $name => $template) {
            $templates[$name] = CertificateTemplateConfig::fromArray($template);
        }

        return new self(
            basePath: $basePath,
            databasePath: $data['database_path'],
            certificationAuthority: CertificationAuthorityConfig::fromArray($data['certification_authority']),
            certificateTemplates: $templates,
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
