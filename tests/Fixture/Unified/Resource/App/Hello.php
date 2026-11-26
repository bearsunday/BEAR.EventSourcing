<?php

declare(strict_types=1);

namespace FakeVendor\Unified\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

final class Hello extends ResourceObject
{
    #[Link(rel: 'greet', href: 'app://self/greeting{?name}')]
    public function onGet(string $name): static
    {
        $this->body = ['name' => $name];

        return $this;
    }
}
