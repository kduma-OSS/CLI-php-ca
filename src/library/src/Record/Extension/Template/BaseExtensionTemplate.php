<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolverFactory;
use KDuma\PhpCA\Record\Extension\Resolver\InputMultipleResolver;
use KDuma\PhpCA\Record\Extension\Resolver\InputResolver;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use ReflectionClass;

abstract class BaseExtensionTemplate
{
    abstract public function resolve(IssuanceContext $context): BaseExtension;

    abstract public static function name(): string;

    abstract public function toArray(): array;

    abstract public static function fromArray(array $data): static;

    abstract public function isCritical(): bool;

    /**
     * Collect all input resolvers from this template's properties.
     *
     * @return array<string, InputResolver|InputMultipleResolver> Keyed by label
     */
    public function getRequiredInputs(): array
    {
        $inputs = [];
        $ref = new ReflectionClass($this);

        foreach ($ref->getProperties() as $prop) {
            if (! $prop->isInitialized($this)) {
                continue;
            }

            $value = $prop->getValue($this);
            $inputs = array_merge($inputs, ExtensionValueResolverFactory::collectInputResolvers($value));
        }

        return $inputs;
    }
}
