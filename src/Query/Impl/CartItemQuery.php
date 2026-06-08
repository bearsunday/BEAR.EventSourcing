<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\CartItemQueryInterface;
use BEAR\EventSourcing\Query\CartQueryInterface;
use DateTimeImmutable;

class CartItemQuery implements CartItemQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo, private readonly CartQueryInterface $cartQuery)
    {
    }

    public function findByCartKeyOrCustomerId(string|null $cartKey, int|null $customerId): array
    {
        $cart = $this->cartQuery->findByKeyOrCustomerId($cartKey, $customerId);

        return $cart['items'] ?? [];
    }

    public function findById(int $id): array|null
    {
        return $this->pdo->fetchOne('SELECT * FROM cart_item WHERE id = :id', ['id' => $id]) ?: null;
    }

    public function addItem(int $productClassId, int $quantity, string|null $cartKey, int|null $customerId): int
    {
        $cartId = $this->cartQuery->createOrGet($cartKey, $customerId);
        $existing = $this->pdo->fetchOne('SELECT * FROM cart_item WHERE cart_id = :cart_id AND product_class_id = :product_class_id', ['cart_id' => $cartId, 'product_class_id' => $productClassId]);
        $pc = $this->pdo->fetchOne('SELECT price02 FROM product_class WHERE id = :id', ['id' => $productClassId]);
        $price = $pc['price02'] ?? '0';
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            $this->pdo->perform('UPDATE cart_item SET quantity = :quantity, update_date = :update_date WHERE id = :id', ['id' => $existing['id'], 'quantity' => $newQty, 'update_date' => $now]);

            return $existing['id'];
        }

        $this->pdo->perform('INSERT INTO cart_item (cart_id, product_class_id, quantity, price, create_date, update_date) VALUES (:cart_id, :product_class_id, :quantity, :price, :create_date, :update_date)', ['cart_id' => $cartId, 'product_class_id' => $productClassId, 'quantity' => $quantity, 'price' => $price, 'create_date' => $now, 'update_date' => $now]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateQuantity(int $id, int $quantity): void
    {
        $this->pdo->perform('UPDATE cart_item SET quantity = :quantity, update_date = :update_date WHERE id = :id', ['id' => $id, 'quantity' => $quantity, 'update_date' => (new DateTimeImmutable())->format('Y-m-d H:i:s')]);
    }

    public function removeItem(int $id): void
    {
        $this->pdo->perform('DELETE FROM cart_item WHERE id = :id', ['id' => $id]);
    }
}
