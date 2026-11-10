<?php

declare(strict_types=1);

namespace FakeVendor\Unified\Resource\App;

use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

/**
 * Its embedded greeting is run by the cache on save, from inside the woven onGet().
 *
 * @psalm-suppress ClassMustBeFinal Ray.Aop weaves a subclass for #[Cacheable].
 * @psalm-suppress NoInterfaceProperties,MixedMethodCall
 *     The resource client is driven through its magic verb properties.
 */
#[Cacheable]
class Profile extends ResourceObject
{
    public function __construct(
        private readonly ResourceInterface $resource,
    ) {
    }

    public function onGet(string $name): static
    {
        $this->body = ['greeting' => $this->resource->get->uri('app://self/greeting')->withQuery(['name' => $name])];

        return $this;
    }
}
