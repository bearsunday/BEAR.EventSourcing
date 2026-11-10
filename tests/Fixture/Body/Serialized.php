<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Body;

use JsonSerializable;

final readonly class Serialized implements JsonSerializable
{
    public function __construct(
        private mixed $value,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
