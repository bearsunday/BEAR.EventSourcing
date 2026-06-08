<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Category query interface
 */
interface CategoryQueryInterface
{
    /**
     * Find categories by parent ID
     *
     * @param int|null $parentId Parent category ID (null for root)
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByParentId(int|null $parentId = null): array;

    /**
     * Get category tree
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTree(): array;

    /**
     * Find category by ID
     *
     * @param int $id Category ID
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): array|null;

    /**
     * Create a new category
     *
     * @param array<string, mixed> $data Category data
     *
     * @return int Created category ID
     */
    public function create(array $data): int;

    /**
     * Update a category
     *
     * @param int                  $id   Category ID
     * @param array<string, mixed> $data Update data
     */
    public function update(int $id, array $data): void;

    /**
     * Delete a category
     *
     * @param int $id Category ID
     */
    public function delete(int $id): void;

    /**
     * Get category path (ancestors)
     *
     * @param int $id Category ID
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPath(int $id): array;
}
