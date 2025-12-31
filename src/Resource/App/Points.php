<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Annotation\RequireAuth;
use BearEccube\Query\PointQueryInterface;

class Points extends ResourceObject
{
    public function __construct(
        private readonly PointQueryInterface $query
    ) {}

    #[RequireAuth]
    public function onGet(int $customer_id, int $limit = 20, int $offset = 0): static
    {
        $balance = $this->query->getBalance($customer_id);
        $history = $this->query->getHistory($customer_id, $limit, $offset);

        $this->body = [
            'balance' => $balance,
            'history' => $history,
        ];
        return $this;
    }
}
