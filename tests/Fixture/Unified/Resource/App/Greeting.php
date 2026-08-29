<?php

declare(strict_types=1);

namespace FakeVendor\Unified\Resource\App;

use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\ResourceObject;

/**
 * Ray.Aop weaves a subclass for #[Cacheable], so this fixture cannot be final.
 *
 * @psalm-suppress ClassMustBeFinal
 */
#[Cacheable]
class Greeting extends ResourceObject
{
    public function onGet(string $name): static
    {
        $this->body = ['greeting' => 'Hello ' . $name];

        return $this;
    }
}
