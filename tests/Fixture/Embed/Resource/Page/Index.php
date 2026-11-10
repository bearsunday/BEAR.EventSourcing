<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Embed\Resource\Page;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

/**
 * Embeds a child the way #[Embed] does: an unrun request in the body.
 *
 * @psalm-suppress NoInterfaceProperties,MixedMethodCall
 *     The resource client is driven through its magic verb properties.
 */
final class Index extends ResourceObject
{
    public function __construct(
        private readonly ResourceInterface $resource,
    ) {
    }

    public function onGet(): static
    {
        $this->body = [
            'title' => 'index',
            'child' => $this->resource->get->uri('app://self/child')->withQuery(['id' => 'lazy']),
        ];

        return $this;
    }
}
