<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Caller\Resource\App;

use BEAR\Resource\ResourceObject;

final class Nav extends ResourceObject
{
    public function onGet(): static
    {
        $this->body = ['name' => 'nav'];

        return $this;
    }
}
