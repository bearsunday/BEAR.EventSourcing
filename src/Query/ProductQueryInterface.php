<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Product query interface
 */
interface ProductQueryInterface
{
    /**
     * Find all products with optional filters
     *
     * @param int|null    $categoryId Category ID filter
     * @param string|null $name       Name search
     * @param int         $limit      Limit
     * @param int         $offset     Offset
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(
        int|null $categoryId = null,
        string|null $name = null,
        int $limit = 20,
        int $offset = 0,
    ): array;

    /**
     * Count products with optional filters
     *
     * @param int|null    $categoryId Category ID filter
     * @param string|null $name       Name search
     */
    public function count(int|null $categoryId = null, string|null $name = null): int;

    /**
     * Find product by ID
     *
     * @param int $id Product ID
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): array|null;

    /**
     * Create a new product
     *
     * @param array<string, mixed> $data Product data
     *
     * @return int Created product ID
     */
    public function create(array $data): int;

    /**
     * Update a product
     *
     * @param int                  $id   Product ID
     * @param array<string, mixed> $data Update data
     */
    public function update(int $id, array $data): void;

    /**
     * Delete a product
     *
     * @param int $id Product ID
     */
    public function delete(int $id): void;
}
