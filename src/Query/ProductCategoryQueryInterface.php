<?php

declare(strict_types=1);

namespace BearEccube\Query;

/**
 * Product category query interface
 */
interface ProductCategoryQueryInterface
{
    /**
     * Find categories by product ID
     *
     * @param int $productId Product ID
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByProductId(int $productId): array;

    /**
     * Add product to category
     *
     * @param int $productId  Product ID
     * @param int $categoryId Category ID
     */
    public function addCategory(int $productId, int $categoryId): void;

    /**
     * Remove product from category
     *
     * @param int $productId  Product ID
     * @param int $categoryId Category ID
     */
    public function removeCategory(int $productId, int $categoryId): void;
}
