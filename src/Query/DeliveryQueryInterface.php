<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Delivery query interface
 */
interface DeliveryQueryInterface
{
    /**
     * Find all available deliveries
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array;

    /**
     * Find delivery by ID
     *
     * @param int $id Delivery ID
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array;

    /**
     * Get delivery fee by delivery ID and prefecture ID
     *
     * @param int $deliveryId Delivery ID
     * @param int $prefId     Prefecture ID
     *
     * @return string Delivery fee
     */
    public function getDeliveryFee(int $deliveryId, int $prefId): string;
}
