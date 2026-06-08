<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Payment query interface
 */
interface PaymentQueryInterface
{
    /**
     * Find all available payments
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array;

    /**
     * Find payment by ID
     *
     * @param int $id Payment ID
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): array|null;

    /**
     * Find payments by delivery ID
     *
     * @param int $deliveryId Delivery ID
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByDeliveryId(int $deliveryId): array;
}
