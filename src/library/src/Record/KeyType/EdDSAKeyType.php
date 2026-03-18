<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\KeyType;

use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;

readonly class EdDSAKeyType extends BaseKeyType
{
    public function __construct(
        public EdDSACurve $curve,
    ) {}

    public function getType(): string
    {
        return 'eddsa';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'curve' => $this->curve->value,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            curve: EdDSACurve::from($data['curve']),
        );
    }
}
