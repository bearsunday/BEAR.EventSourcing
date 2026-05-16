<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Customer query interface
 */
interface CustomerQueryInterface
{
    /**
     * Find all customers with optional filters
     *
     * @param string|null $email  Email search
     * @param string|null $name   Name search
     * @param int|null    $status Status filter
     * @param int         $limit  Limit
     * @param int         $offset Offset
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(
        ?string $email = null,
        ?string $name = null,
        ?int $status = null,
        int $limit = 20,
        int $offset = 0
    ): array;

    /**
     * Count customers with optional filters
     */
    public function count(?string $email = null, ?string $name = null, ?int $status = null): int;

    /**
     * Find customer by ID
     *
     * @param int $id Customer ID
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array;

    /**
     * Find customer by email
     *
     * @param string $email Email address
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array;

    /**
     * Create a new customer
     *
     * @param array<string, mixed> $data Customer data
     *
     * @return int Created customer ID
     */
    public function create(array $data): int;

    /**
     * Update a customer
     *
     * @param int                  $id   Customer ID
     * @param array<string, mixed> $data Update data
     */
    public function update(int $id, array $data): void;

    /**
     * Delete a customer
     *
     * @param int $id Customer ID
     */
    public function delete(int $id): void;

    /**
     * Update customer point
     *
     * @param int $id    Customer ID
     * @param int $point Point to add (negative to subtract)
     */
    public function updatePoint(int $id, int $point): void;
}
