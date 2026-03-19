<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Converter;

use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;
use KDuma\SimpleDAL\Typed\Contracts\Converter\FieldConverterInterface;

class ExtensionTemplatesConverter implements FieldConverterInterface
{
    public function fromStorage(mixed $value): mixed
    {
        if (! is_array($value)) {
            return [];
        }

        $templates = [];

        foreach ($value as $extData) {
            if (! is_array($extData) || ! isset($extData['name'])) {
                continue;
            }

            $class = ExtensionRegistry::resolveTemplate($extData['name']);
            $templates[] = $class::fromArray($extData);
        }

        return $templates;
    }

    public function toStorage(mixed $value): mixed
    {
        if (! is_array($value)) {
            return [];
        }

        return array_map(
            fn (BaseExtensionTemplate $tpl) => $tpl->toArray(),
            $value,
        );
    }
}
