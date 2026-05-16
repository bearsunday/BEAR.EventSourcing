<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\CustomerQueryInterface;

class Customer extends ResourceObject
{
    public function __construct(
        private readonly CustomerQueryInterface $query
    ) {
    }

    #[Link(rel: 'collection', href: '/customers')]
    #[Link(rel: 'orders', href: '/orders{?customer_id}')]
    #[JsonSchema('customer.get.json')]
    public function onGet(int $id): static
    {
        $customer = $this->query->findById($id);

        if ($customer === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => 'Customer not found'];
            return $this;
        }

        $this->body = $customer;

        return $this;
    }
}
