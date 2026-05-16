<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\OrderQueryInterface;

class Order extends ResourceObject
{
    public function __construct(
        private readonly OrderQueryInterface $query
    ) {
    }

    #[Link(rel: 'collection', href: '/orders')]
    #[Link(rel: 'customer', href: '/customer{?id}')]
    #[JsonSchema('order.get.json')]
    public function onGet(int $id): static
    {
        $order = $this->query->findById($id);

        if ($order === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => 'Order not found'];
            return $this;
        }

        $this->body = $order;

        return $this;
    }
}
