<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

use RuntimeException;

class PresetInputProvider implements InputProviderInterface
{
    /**
     * @param  array<string, string|string[]>  $answers  Keyed by alias
     */
    public function __construct(
        private readonly array $answers = [],
    ) {}

    public function ask(string $alias, string $label, ?string $default = null): string
    {
        if (isset($this->answers[$alias])) {
            $value = $this->answers[$alias];

            return is_array($value) ? implode(',', $value) : $value;
        }

        if ($default !== null) {
            return $default;
        }

        throw new RuntimeException("No preset answer for input \"{$alias}\" ({$label})");
    }

    public function askMultiple(string $alias, string $label): array
    {
        if (isset($this->answers[$alias])) {
            $value = $this->answers[$alias];

            return is_array($value) ? $value : array_filter(explode(',', $value));
        }

        throw new RuntimeException("No preset answer for input \"{$alias}\" ({$label})");
    }
}
