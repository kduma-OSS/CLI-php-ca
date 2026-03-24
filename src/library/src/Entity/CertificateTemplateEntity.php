<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use DateInterval;
use KDuma\PhpCA\Record\CertificateTemplateRecord;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

/**
 * @extends BaseEntity<CertificateTemplateRecord>
 */
class CertificateTemplateEntity extends BaseEntity
{
    public string $displayName;

    public ?string $parentId = null;

    public ?DateInterval $validity = null;

    /** @var BaseExtensionTemplate[] */
    public array $extensions = [];

    /**
     * Resolve the effective extensions by walking the inheritance chain.
     * Child extensions override parent extensions with the same name.
     *
     * @return BaseExtensionTemplate[]
     */
    public function getEffectiveExtensions(?CertificateTemplateEntityCollection $collection = null): array
    {
        $parentExtensions = [];

        if ($this->parentId !== null && $collection !== null) {
            $parent = $collection->findOrNull($this->parentId);
            if ($parent !== null) {
                $parentExtensions = $parent->getEffectiveExtensions($collection);
            }
        }

        // Index parent extensions by name
        $merged = [];
        foreach ($parentExtensions as $ext) {
            $merged[$ext::name()] = $ext;
        }

        // Child overrides parent by name
        foreach ($this->extensions as $ext) {
            $merged[$ext::name()] = $ext;
        }

        return array_values($merged);
    }

    /**
     * Resolve the effective validity by walking the inheritance chain.
     */
    public function getEffectiveValidity(?CertificateTemplateEntityCollection $collection = null): ?DateInterval
    {
        if ($this->validity !== null) {
            return $this->validity;
        }

        if ($this->parentId !== null && $collection !== null) {
            $parent = $collection->findOrNull($this->parentId);
            if ($parent !== null) {
                return $parent->getEffectiveValidity($collection);
            }
        }

        return null;
    }

    /**
     * @param  CertificateTemplateEntity  $entity
     * @param  CertificateTemplateRecord  $record
     */
    protected static function _populateFromRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CertificateTemplateRecord);
        assert($entity instanceof CertificateTemplateEntity);

        $entity->displayName = $record->displayName;
        $entity->parentId = $record->parentId;
        $entity->validity = $record->validity;
        $entity->extensions = $record->extensions;
    }

    /**
     * @param  CertificateTemplateEntity  $entity
     * @param  CertificateTemplateRecord  $record
     */
    protected static function _populateToRecord(BaseEntity $entity, TypedRecord $record): void
    {
        assert($record instanceof CertificateTemplateRecord);
        assert($entity instanceof CertificateTemplateEntity);

        $record->displayName = $entity->displayName;
        $record->parentId = $entity->parentId;
        $record->validity = $entity->validity;
        $record->extensions = $entity->extensions;
    }
}
