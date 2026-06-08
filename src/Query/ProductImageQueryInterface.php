<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

/**
 * Product image query interface
 */
interface ProductImageQueryInterface
{
    /**
     * Find product images by product ID
     *
     * @param int $productId Product ID
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByProductId(int $productId): array;

    /**
     * Create a new product image
     *
     * @param array<string, mixed> $data Product image data
     *
     * @return int Created product image ID
     */
    public function create(array $data): int;

    /**
     * Delete a product image
     *
     * @param int $id Product image ID
     */
    public function delete(int $id): void;

    /**
     * Update sort order
     *
     * @param int $id     Product image ID
     * @param int $sortNo New sort order
     */
    public function updateSortNo(int $id, int $sortNo): void;
}
