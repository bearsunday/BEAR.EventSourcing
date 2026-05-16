<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Cart item query interface
 */
interface CartItemQueryInterface
{
    /**
     * Find cart items by cart key or customer ID
     *
     * @param string|null $cartKey    Cart key
     * @param int|null    $customerId Customer ID
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByCartKeyOrCustomerId(?string $cartKey, ?int $customerId): array;

    /**
     * Find cart item by ID
     *
     * @param int $id Cart item ID
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array;

    /**
     * Add item to cart
     *
     * @param int         $productClassId Product class ID
     * @param int         $quantity       Quantity
     * @param string|null $cartKey        Cart key
     * @param int|null    $customerId     Customer ID
     *
     * @return int Cart item ID
     */
    public function addItem(
        int $productClassId,
        int $quantity,
        ?string $cartKey,
        ?int $customerId
    ): int;

    /**
     * Update cart item quantity
     *
     * @param int $id       Cart item ID
     * @param int $quantity New quantity
     */
    public function updateQuantity(int $id, int $quantity): void;

    /**
     * Remove item from cart
     *
     * @param int $id Cart item ID
     */
    public function removeItem(int $id): void;
}
