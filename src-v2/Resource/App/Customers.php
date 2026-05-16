<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\CustomerQueryInterface;

class Customers extends ResourceObject
{
    public function __construct(
        private readonly CustomerQueryInterface $query
    ) {
    }

    #[Link(rel: 'item', href: '/customer{?id}')]
    #[JsonSchema('customers.get.json')]
    public function onGet(
        ?string $name = null,
        ?string $email = null,
        ?int $status_id = null,
        int $limit = 20,
        int $offset = 0
    ): static {
        $this->body = $this->query->findList($name, $email, $status_id, $limit, $offset);

        return $this;
    }
}
