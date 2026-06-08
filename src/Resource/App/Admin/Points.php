<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\PointQueryInterface;
use BEAR\Resource\ResourceObject;

class Points extends ResourceObject
{
    public function __construct(
        private readonly PointQueryInterface $query,
    ) {
    }

    #[RequireAuth(role: 'admin')]
    public function onGet(int $customer_id, int $limit = 50, int $offset = 0): static
    {
        $balance = $this->query->getBalance($customer_id);
        $history = $this->query->getHistory($customer_id, $limit, $offset);

        $this->body = [
            'customer_id' => $customer_id,
            'balance' => $balance,
            'history' => $history,
        ];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPost(int $customer_id, int $point, string $reason): static
    {
        $id = $this->query->adjustPoints($customer_id, $point, $reason);
        $newBalance = $this->query->getBalance($customer_id);

        $this->code = 201;
        $this->body = [
            'id' => $id,
            'customer_id' => $customer_id,
            'adjusted' => $point,
            'new_balance' => $newBalance,
        ];

        return $this;
    }
}
