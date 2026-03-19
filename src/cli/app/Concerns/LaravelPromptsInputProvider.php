<?php

namespace App\Concerns;

use KDuma\PhpCA\Record\Extension\Resolver\InputProviderInterface;

use function Laravel\Prompts\text;

class LaravelPromptsInputProvider implements InputProviderInterface
{
    /**
     * @param array<string, string|string[]> $presets Pre-set answers keyed by alias (bypass prompts)
     */
    public function __construct(
        private readonly array $presets = [],
    ) {}

    public function ask(string $alias, string $label, ?string $default = null): string
    {
        if (isset($this->presets[$alias])) {
            $value = $this->presets[$alias];

            return is_array($value) ? implode(',', $value) : $value;
        }

        return text($label, default: $default ?? '', required: $default === null);
    }

    public function askMultiple(string $alias, string $label): array
    {
        if (isset($this->presets[$alias])) {
            $value = $this->presets[$alias];

            return is_array($value) ? $value : array_filter(array_map('trim', explode(',', $value)));
        }

        $input = text("{$label} (comma-separated)");

        return array_filter(array_map('trim', explode(',', $input)));
    }
}
