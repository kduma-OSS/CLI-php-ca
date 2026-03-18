<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\KeyType;

use KDuma\PhpCA\Record\KeyType\Enum\DsaParameterSize;

readonly class DSAKeyType extends BaseKeyType
{
    public function __construct(
        public DsaParameterSize $parameters,
    ) {}

    public function getType(): string
    {
        return 'dsa';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'parameters' => $this->parameters->value,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            parameters: DsaParameterSize::from($data['parameters']),
        );
    }
}
