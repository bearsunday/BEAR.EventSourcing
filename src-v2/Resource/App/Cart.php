<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\CartQueryInterface;

class Cart extends ResourceObject
{
    public function __construct(
        private readonly CartQueryInterface $query
    ) {
    }

    #[Link(rel: 'collection', href: '/carts')]
    #[Link(rel: 'customer', href: '/customer{?id}')]
    #[JsonSchema('cart.get.json')]
    public function onGet(int $id): static
    {
        $cart = $this->query->findById($id);

        if ($cart === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => 'Cart not found'];
            return $this;
        }

        $this->body = $cart;
        return $this;
    }
}
