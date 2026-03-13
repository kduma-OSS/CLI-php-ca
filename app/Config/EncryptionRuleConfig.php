<?php

namespace App\Config;

use App\Config\Enums\EncryptionSourceType;

readonly class EncryptionRuleConfig
{
    /**
     * @param  array<EncryptionRuleApplyTo>  $applyTo
     */
    public function __construct(
        public EncryptionSourceType $type,
        public string $name,
        public array $applyTo,
    ) {}

    public static function fromArray(array $data): self
    {
        $applyTo = [];

        foreach ($data['apply_to'] as $key => $value) {
            $applyTo[] = EncryptionRuleApplyTo::fromEntry($key, $value);
        }

        return new self(
            type: EncryptionSourceType::from($data['type']),
            name: $data['name'],
            applyTo: $applyTo,
        );
    }
}
