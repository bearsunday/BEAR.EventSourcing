<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\CartQueryInterface;

class Carts extends ResourceObject
{
    public function __construct(
        private readonly CartQueryInterface $query
    ) {
    }

    #[Link(rel: 'item', href: '/cart{?id}')]
    #[JsonSchema('carts.get.json')]
    public function onGet(?int $customer_id = null, int $limit = 20, int $offset = 0): static
    {
        $this->body = $this->query->findList($customer_id, $limit, $offset);
        return $this;
    }
}
