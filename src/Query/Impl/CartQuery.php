<?php

declare(strict_types=1);

namespace BearEccube\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BearEccube\Query\CartQueryInterface;
use DateTimeImmutable;

class CartQuery implements CartQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}

    public function findByKeyOrCustomerId(?string $cartKey, ?int $customerId): ?array
    {
        if ($customerId) {
            $cart = $this->pdo->fetchOne('SELECT * FROM cart WHERE customer_id = :customer_id', ['customer_id' => $customerId]);
        } elseif ($cartKey) {
            $cart = $this->pdo->fetchOne('SELECT * FROM cart WHERE cart_key = :cart_key', ['cart_key' => $cartKey]);
        } else {
            return null;
        }
        if (!$cart) return null;
        $items = $this->pdo->fetchAll('SELECT ci.*, pc.code, p.name AS product_name FROM cart_item ci JOIN product_class pc ON ci.product_class_id = pc.id JOIN product p ON pc.product_id = p.id WHERE ci.cart_id = :cart_id', ['cart_id' => $cart['id']]);
        $cart['items'] = $items;
        $cart['total_quantity'] = array_sum(array_column($items, 'quantity'));
        $cart['total_price'] = array_reduce($items, fn($sum, $item) => bcadd($sum, bcmul($item['price'], (string)$item['quantity'])), '0');
        return $cart;
    }

    public function createOrGet(?string $cartKey, ?int $customerId): int
    {
        $existing = $this->findByKeyOrCustomerId($cartKey, $customerId);
        if ($existing) return $existing['id'];
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform('INSERT INTO cart (cart_key, customer_id, create_date, update_date) VALUES (:cart_key, :customer_id, :create_date, :update_date)', ['cart_key' => $cartKey, 'customer_id' => $customerId, 'create_date' => $now, 'update_date' => $now]);
        return (int)$this->pdo->lastInsertId();
    }

    public function clear(?string $cartKey, ?int $customerId): void
    {
        $cart = $this->findByKeyOrCustomerId($cartKey, $customerId);
        if ($cart) { $this->pdo->perform('DELETE FROM cart_item WHERE cart_id = :cart_id', ['cart_id' => $cart['id']]); }
    }

    public function merge(string $cartKey, int $customerId): void
    {
        $guestCart = $this->pdo->fetchOne('SELECT * FROM cart WHERE cart_key = :cart_key', ['cart_key' => $cartKey]);
        if (!$guestCart) return;
        $customerCartId = $this->createOrGet(null, $customerId);
        $this->pdo->perform('UPDATE cart_item SET cart_id = :new_cart_id WHERE cart_id = :old_cart_id', ['new_cart_id' => $customerCartId, 'old_cart_id' => $guestCart['id']]);
        $this->pdo->perform('DELETE FROM cart WHERE id = :id', ['id' => $guestCart['id']]);
    }
}
