<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Converter;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\SimpleDAL\Typed\Contracts\Converter\FieldConverterInterface;

class ExtensionsConverter implements FieldConverterInterface
{
    public function fromStorage(mixed $value): mixed
    {
        if (! is_array($value)) {
            return [];
        }

        $extensions = [];

        foreach ($value as $extData) {
            if (! is_array($extData) || ! isset($extData['name'])) {
                continue;
            }

            $class = ExtensionRegistry::resolve($extData['name']);
            $extensions[] = $class::fromArray($extData);
        }

        return $extensions;
    }

    public function toStorage(mixed $value): mixed
    {
        if (! is_array($value)) {
            return [];
        }

        return array_map(
            fn (BaseExtension $ext) => $ext->toArray(),
            $value,
        );
    }
}
