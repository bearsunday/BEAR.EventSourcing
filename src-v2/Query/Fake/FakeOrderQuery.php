<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use BearEccube\Query\OrderQueryInterface;

class FakeOrderQuery extends AbstractFakeQuery implements OrderQueryInterface
{
    protected function fakeName(): string
    {
        return 'order';
    }

    public function findList(
        ?int $customerId = null,
        ?int $statusId = null,
        ?string $orderNo = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $orders = $this->loadItems();

        if ($customerId !== null) {
            $orders = array_values(array_filter(
                $orders,
                static fn($o) => ($o['customer']['id'] ?? null) === $customerId
            ));
        }

        if ($orderNo !== null) {
            $orders = array_values(array_filter(
                $orders,
                static fn($o) => str_contains($o['order_no'] ?? '', $orderNo)
            ));
        }

        return [
            'orders' => $orders,
            'total' => count($orders),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->findItemById($id);
    }

    public function findByOrderNo(string $orderNo): ?array
    {
        foreach ($this->loadItems() as $order) {
            if (($order['order_no'] ?? '') === $orderNo) {
                return $order;
            }
        }
        return null;
    }
}
