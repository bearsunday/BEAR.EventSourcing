<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Caller;

use BEAR\Resource\ResourceInterface;

final readonly class SiteSetting
{
    public function __construct(
        private ResourceInterface $resource,
    ) {
    }

    public function name(): mixed
    {
        return $this->resource->get('app://self/setting')->body;
    }
}
