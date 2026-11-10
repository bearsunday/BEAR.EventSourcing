<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Embed\Resource\App;

use BEAR\EventSourcing\Tests\Fixture\Embed\Trace;
use BEAR\Resource\ResourceObject;

final class Child extends ResourceObject
{
    public function onGet(string $id): static
    {
        Trace::$events[] = "run app://self/child?id={$id}";
        $this->body = ['id' => $id];

        return $this;
    }
}
