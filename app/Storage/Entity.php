<?php

namespace App\Storage;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class Entity implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $id,
    ) {}

    abstract public static function fromArray(string $id, array $data): static;

    abstract public function toArray(): array;

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
