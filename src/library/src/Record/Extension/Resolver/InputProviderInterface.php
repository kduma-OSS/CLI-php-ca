<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

interface InputProviderInterface
{
    /**
     * @param  string  $alias  Machine-readable key (e.g. "dns-names")
     * @param  string  $label  Human-readable label (e.g. "DNS names")
     */
    public function ask(string $alias, string $label, ?string $default = null): string;

    /**
     * @param  string  $alias  Machine-readable key (e.g. "dns-names")
     * @param  string  $label  Human-readable label (e.g. "DNS names")
     * @return string[]
     */
    public function askMultiple(string $alias, string $label): array;
}
