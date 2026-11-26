<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Link\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

final class Form extends ResourceObject
{
    #[Link(rel: 'goItem', href: 'app://self/item{?id}')]
    public function onGet(): static
    {
        $this->body = ['id' => '7'];

        return $this;
    }
}
