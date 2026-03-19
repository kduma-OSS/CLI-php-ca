<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

use InvalidArgumentException;

class ExtensionValueResolverFactory
{
    /** @var array<string, class-string<ExtensionValueResolver>> */
    private static array $types = [
        'literal' => LiteralResolver::class,
        'input' => InputResolver::class,
        'input-multiple' => InputMultipleResolver::class,
        'subject-key-fingerprint' => SubjectKeyFingerprintResolver::class,
        'ca-key-fingerprint' => CaKeyFingerprintResolver::class,
        'subject-field' => SubjectFieldResolver::class,
        'template' => TemplateStringResolver::class,
        'relative-date' => RelativeDateResolver::class,
    ];

    /**
     * Parse a resolver from raw config. Strings become LiteralResolver.
     */
    public static function fromMixed(mixed $data): ExtensionValueResolver|array|string
    {
        if (is_string($data)) {
            return new LiteralResolver($data);
        }

        if (is_array($data) && isset($data['type'])) {
            return self::fromArray($data);
        }

        // Array of values — could be literal array or array of resolvers
        if (is_array($data)) {
            return array_map(fn ($item) => self::fromMixed($item), $data);
        }

        return new LiteralResolver((string) $data);
    }

    public static function fromArray(array $data): ExtensionValueResolver
    {
        $type = $data['type'] ?? throw new InvalidArgumentException('Resolver must have a "type" field.');

        if (! isset(self::$types[$type])) {
            throw new InvalidArgumentException("Unknown resolver type: {$type}");
        }

        return self::$types[$type]::fromArray($data);
    }

    public static function toMixed(mixed $value): mixed
    {
        if ($value instanceof ExtensionValueResolver) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map(fn ($item) => self::toMixed($item), $value);
        }

        return $value;
    }

    /**
     * Resolve a field value — handles resolvers, arrays of resolvers, and plain values.
     */
    public static function resolveField(mixed $field, IssuanceContext $context): mixed
    {
        if ($field instanceof ExtensionValueResolver) {
            return $field->resolve($context);
        }

        if (is_array($field)) {
            $result = [];
            foreach ($field as $item) {
                $resolved = self::resolveField($item, $context);
                if (is_array($resolved)) {
                    $result = array_merge($result, $resolved);
                } else {
                    $result[] = $resolved;
                }
            }

            return $result;
        }

        return $field;
    }

    /**
     * Recursively collect all InputResolver/InputMultipleResolver instances from a mixed field.
     *
     * @return array<string, InputResolver|InputMultipleResolver> Keyed by alias
     */
    public static function collectInputResolvers(mixed $field): array
    {
        $result = [];

        if ($field instanceof InputResolver || $field instanceof InputMultipleResolver) {
            $result[$field->alias] = $field;
        } elseif (is_array($field)) {
            foreach ($field as $item) {
                $result = array_merge($result, self::collectInputResolvers($item));
            }
        }

        return $result;
    }
}
