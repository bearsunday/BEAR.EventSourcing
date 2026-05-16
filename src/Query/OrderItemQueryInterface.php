<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Order item query interface
 */
interface OrderItemQueryInterface
{
    /**
     * Find order items by order ID
     *
     * @param int $orderId Order ID
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByOrderId(int $orderId): array;

    /**
     * Create order items
     *
     * @param int                        $orderId Order ID
     * @param array<array<string, mixed>> $items   Items data
     */
    public function createItems(int $orderId, array $items): void;
}
