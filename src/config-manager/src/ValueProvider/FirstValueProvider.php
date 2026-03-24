<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;
use RuntimeException;

#[ValueProviderType('first')]
readonly class FirstValueProvider extends ValueProvider
{
    /**
     * @param  ValueProvider[]  $candidates
     */
    public function __construct(
        public array $candidates,
    ) {}

    public function resolve(): string
    {
        foreach ($this->candidates as $candidate) {
            try {
                $result = $candidate->resolve();

                if ($result !== '') {
                    return $result;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        throw new RuntimeException('None of the key discovery candidates resolved successfully.');
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory;

        $candidates = array_map(
            fn (array $item) => $factory->fromArray($item, $basePath),
            $data['candidates'] ?? throw new \InvalidArgumentException('First key discovery requires "candidates".'),
        );

        return new static(candidates: $candidates);
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'candidates' => array_map(fn (ValueProvider $kd) => $kd->toArray(), $this->candidates),
        ];
    }
}
