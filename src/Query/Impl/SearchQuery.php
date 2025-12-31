<?php

declare(strict_types=1);

namespace BearEccube\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BearEccube\Query\SearchQueryInterface;

class SearchQuery implements SearchQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}

    public function searchProducts(array $filters, string $sort, string $order, int $limit, int $offset): array
    {
        [$sql, $params] = $this->buildSearchQuery($filters, false);

        $orderBy = match ($sort) {
            'name' => 'p.name',
            'price' => 'min_price',
            default => 'p.update_date',
        };
        $orderDir = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY {$orderBy} {$orderDir} LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->pdo->fetchAll($sql, $params);
    }

    public function countProducts(array $filters): int
    {
        [$sql, $params] = $this->buildSearchQuery($filters, true);
        return (int)$this->pdo->fetchValue($sql, $params);
    }

    public function getFacets(array $filters): array
    {
        // Get category counts
        $categorySql = 'SELECT c.id, c.name, COUNT(DISTINCT pc.product_id) as count
                        FROM category c
                        JOIN product_category pc ON c.id = pc.category_id
                        JOIN product p ON pc.product_id = p.id
                        WHERE p.product_status_id = 1
                        GROUP BY c.id
                        ORDER BY count DESC';
        $categories = $this->pdo->fetchAll($categorySql);

        // Get price ranges
        $priceRanges = [
            ['min' => '0', 'max' => '1000', 'label' => '~¥1,000'],
            ['min' => '1000', 'max' => '5000', 'label' => '¥1,000~¥5,000'],
            ['min' => '5000', 'max' => '10000', 'label' => '¥5,000~¥10,000'],
            ['min' => '10000', 'max' => null, 'label' => '¥10,000~'],
        ];

        return [
            'categories' => $categories,
            'price_ranges' => $priceRanges,
        ];
    }

    private function buildSearchQuery(array $filters, bool $countOnly): array
    {
        $params = [];

        if ($countOnly) {
            $select = 'SELECT COUNT(DISTINCT p.id)';
        } else {
            $select = 'SELECT DISTINCT p.id, p.name, p.description_list,
                       MIN(pc2.price02) as min_price, MAX(pc2.price02) as max_price,
                       (SELECT file_name FROM product_image pi WHERE pi.product_id = p.id ORDER BY sort_no LIMIT 1) as main_image';
        }

        $sql = "{$select} FROM product p
                LEFT JOIN product_class pc2 ON pc2.product_id = p.id AND pc2.visible = 1";

        $where = ['p.product_status_id = 1'];

        if (!empty($filters['category_id'])) {
            $sql .= ' JOIN product_category pcat ON pcat.product_id = p.id';
            $where[] = 'pcat.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['keyword'])) {
            $where[] = '(p.name LIKE :keyword OR p.search_word LIKE :keyword OR p.description_detail LIKE :keyword)';
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['price_min'])) {
            $where[] = 'pc2.price02 >= :price_min';
            $params['price_min'] = $filters['price_min'];
        }

        if (!empty($filters['price_max'])) {
            $where[] = 'pc2.price02 <= :price_max';
            $params['price_max'] = $filters['price_max'];
        }

        if (!empty($filters['in_stock'])) {
            $where[] = '(pc2.stock_unlimited = 1 OR pc2.stock > 0)';
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);

        if (!$countOnly) {
            $sql .= ' GROUP BY p.id';
        }

        return [$sql, $params];
    }
}
