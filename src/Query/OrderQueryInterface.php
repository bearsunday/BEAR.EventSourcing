<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Order query interface
 */
interface OrderQueryInterface
{
    /**
     * Find all orders with optional filters
     *
     * @param int|null    $customerId Customer ID filter
     * @param int|null    $status     Status filter
     * @param string|null $orderNo    Order number search
     * @param string|null $dateFrom   Date from filter
     * @param string|null $dateTo     Date to filter
     * @param int         $limit      Limit
     * @param int         $offset     Offset
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(
        int|null $customerId = null,
        int|null $status = null,
        string|null $orderNo = null,
        string|null $dateFrom = null,
        string|null $dateTo = null,
        int $limit = 20,
        int $offset = 0,
    ): array;

    /**
     * Count orders with optional filters
     */
    public function count(
        int|null $customerId = null,
        int|null $status = null,
        string|null $orderNo = null,
        string|null $dateFrom = null,
        string|null $dateTo = null,
    ): int;

    /**
     * Find order by ID
     *
     * @param int $id Order ID
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): array|null;

    /**
     * Find order by order number
     *
     * @param string $orderNo Order number
     *
     * @return array<string, mixed>|null
     */
    public function findByOrderNo(string $orderNo): array|null;

    /**
     * Create a new order
     *
     * @param array<string, mixed> $data Order data
     *
     * @return int Created order ID
     */
    public function create(array $data): int;

    /**
     * Update an order
     *
     * @param int                  $id   Order ID
     * @param array<string, mixed> $data Update data
     */
    public function update(int $id, array $data): void;

    /**
     * Cancel an order
     *
     * @param int $id Order ID
     */
    public function cancel(int $id): void;
}
