<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\KeyType;

use KDuma\PhpCA\Record\KeyType\Enum\EcCurve;

readonly class ECDSAKeyType extends BaseKeyType
{
    public function __construct(
        public EcCurve $curve,
    ) {}

    public function getType(): string
    {
        return 'ecdsa';
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
            curve: EcCurve::from($data['curve']),
        );
    }
}
