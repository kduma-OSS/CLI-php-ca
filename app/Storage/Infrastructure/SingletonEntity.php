<?php

namespace App\Storage\Infrastructure;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class SingletonEntity implements Arrayable, JsonSerializable
{
    abstract public static function fromArray(array $data): static;

    abstract public function toArray(): array;

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
