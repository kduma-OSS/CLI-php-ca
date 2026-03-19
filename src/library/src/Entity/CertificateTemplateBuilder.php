<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use DateInterval;
use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class CertificateTemplateBuilder
{
    private string $displayName = '';
    private ?string $parentId = null;
    private ?DateInterval $validity = null;
    /** @var BaseExtensionTemplate[] */
    private array $extensions = [];

    public function __construct(
        private readonly CertificationAuthority $ca,
        private readonly string $templateId,
    ) {}

    public function displayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function parent(string|CertificateTemplateEntity $parent): static
    {
        $this->parentId = $parent instanceof CertificateTemplateEntity ? $parent->id : $parent;

        return $this;
    }

    public function validity(DateInterval $validity): static
    {
        $this->validity = $validity;

        return $this;
    }

    public function addExtension(BaseExtensionTemplate $extension): static
    {
        $this->extensions[] = $extension;

        return $this;
    }

    public function save(): CertificateTemplateEntity
    {
        if ($this->validity === null && $this->parentId === null) {
            throw new \LogicException('Template validity is required (or inherit from parent).');
        }

        $entity = new CertificateTemplateEntity();
        $entity->id = $this->templateId;
        $entity->displayName = $this->displayName;
        $entity->parentId = $this->parentId;
        $entity->validity = $this->validity;
        $entity->extensions = $this->extensions;

        $this->ca->templates->save($entity);

        return $entity;
    }
}
