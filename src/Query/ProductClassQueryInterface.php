<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Product class query interface
 */
interface ProductClassQueryInterface
{
    /**
     * Find product classes by product ID
     *
     * @param int $productId Product ID
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByProductId(int $productId): array;

    /**
     * Find product class by ID
     *
     * @param int $id Product class ID
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): array|null;

    /**
     * Create a new product class
     *
     * @param array<string, mixed> $data Product class data
     *
     * @return int Created product class ID
     */
    public function create(array $data): int;

    /**
     * Update a product class
     *
     * @param int                  $id   Product class ID
     * @param array<string, mixed> $data Update data
     */
    public function update(int $id, array $data): void;

    /**
     * Delete a product class
     *
     * @param int $id Product class ID
     */
    public function delete(int $id): void;

    /**
     * Update stock
     *
     * @param int $id       Product class ID
     * @param int $quantity Quantity to add (negative to subtract)
     */
    public function updateStock(int $id, int $quantity): void;
}
