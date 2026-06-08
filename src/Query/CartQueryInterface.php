<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Cart query interface
 */
interface CartQueryInterface
{
    /**
     * Find cart by key or customer ID
     *
     * @param string|null $cartKey    Cart key
     * @param int|null    $customerId Customer ID
     *
     * @return array<string, mixed>|null
     */
    public function findByKeyOrCustomerId(string|null $cartKey, int|null $customerId): array|null;

    /**
     * Create or get existing cart
     *
     * @param string|null $cartKey    Cart key
     * @param int|null    $customerId Customer ID
     *
     * @return int Cart ID
     */
    public function createOrGet(string|null $cartKey, int|null $customerId): int;

    /**
     * Clear cart
     *
     * @param string|null $cartKey    Cart key
     * @param int|null    $customerId Customer ID
     */
    public function clear(string|null $cartKey, int|null $customerId): void;

    /**
     * Merge guest cart into customer cart
     *
     * @param string $cartKey    Guest cart key
     * @param int    $customerId Customer ID
     */
    public function merge(string $cartKey, int $customerId): void;
}
