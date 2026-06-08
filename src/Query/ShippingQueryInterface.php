<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Shipping query interface
 */
interface ShippingQueryInterface
{
    /**
     * Find shippings by order ID
     *
     * @param int $orderId Order ID
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByOrderId(int $orderId): array;

    /**
     * Create a shipping
     *
     * @param array<string, mixed> $data Shipping data
     *
     * @return int Created shipping ID
     */
    public function create(array $data): int;

    /**
     * Update a shipping
     *
     * @param int                  $id   Shipping ID
     * @param array<string, mixed> $data Update data
     */
    public function update(int $id, array $data): void;
}
