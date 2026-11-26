<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Link\Resource\App;

use BEAR\Resource\ResourceObject;

final class Item extends ResourceObject
{
    public function onGet(string $id): static
    {
        $this->body = ['id' => $id];

        return $this;
    }
}
