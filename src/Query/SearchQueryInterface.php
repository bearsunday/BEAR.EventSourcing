<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface SearchQueryInterface
{
    /**
     * @param array{keyword?: string, category_id?: int, price_min?: string, price_max?: string, in_stock?: bool} $filters
     */
    public function searchProducts(array $filters, string $sort, string $order, int $limit, int $offset): array;

    /**
     * @param array{keyword?: string, category_id?: int, price_min?: string, price_max?: string, in_stock?: bool} $filters
     */
    public function countProducts(array $filters): int;

    /**
     * Get search facets (category counts, price ranges, etc.)
     *
     * @param array{keyword?: string, category_id?: int, price_min?: string, price_max?: string, in_stock?: bool} $filters
     * @return array{categories: array, price_ranges: array}
     */
    public function getFacets(array $filters): array;
}
