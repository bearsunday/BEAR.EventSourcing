<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\OrderQueryInterface;

class Orders extends ResourceObject
{
    public function __construct(
        private readonly OrderQueryInterface $query
    ) {
    }

    #[Link(rel: 'item', href: '/order{?id}')]
    #[JsonSchema('orders.get.json')]
    public function onGet(
        ?int $customer_id = null,
        ?int $status_id = null,
        ?string $order_no = null,
        int $limit = 20,
        int $offset = 0
    ): static {
        $this->body = $this->query->findList($customer_id, $status_id, $order_no, $limit, $offset);

        return $this;
    }
}
