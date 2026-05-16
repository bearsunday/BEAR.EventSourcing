<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use BearEccube\Query\CartQueryInterface;

class FakeCartQuery extends AbstractFakeQuery implements CartQueryInterface
{
    protected function fakeName(): string
    {
        return 'cart';
    }

    public function findList(?int $customerId = null, int $limit = 20, int $offset = 0): array
    {
        $carts = $this->loadItems();

        if ($customerId !== null) {
            $carts = array_values(array_filter(
                $carts,
                static fn($c) => ($c['customer']['id'] ?? null) === $customerId
            ));
        }

        return [
            'carts' => $carts,
            'total' => count($carts),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->findItemById($id);
    }

    public function findByCartKey(string $cartKey): ?array
    {
        foreach ($this->loadItems() as $cart) {
            if (($cart['cart_key'] ?? '') === $cartKey) {
                return $cart;
            }
        }
        return null;
    }
}
