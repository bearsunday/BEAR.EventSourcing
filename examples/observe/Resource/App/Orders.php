<?php

declare(strict_types=1);

namespace FakeApp\Resource\App;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

/**
 * A live resource whose onPost() performs a nested inventory write through the
 * same invoker, so the observation log records a parent -> child request tree.
 */
final class Orders extends ResourceObject
{
    public function __construct(
        private readonly ResourceInterface $resource,
    ) {
    }

    public function onPost(string $order_id): static
    {
        $this->resource->put->uri('app://self/inventory')([
            'sku' => 'SKU-1',
            'quantity' => 1,
        ]);

        $this->code = 201;
        $this->body = ['order_id' => $order_id, 'status' => 'accepted'];

        return $this;
    }

    public function onGet(string $order_id): static
    {
        $this->body = ['order_id' => $order_id, 'status' => 'accepted'];

        return $this;
    }

    public function onDelete(string $order_id): static
    {
        // A failed state change: observed in the log, but never extracted as an event.
        $this->code = 409;
        $this->body = ['message' => 'Order has open inventory holds'];

        return $this;
    }
}
