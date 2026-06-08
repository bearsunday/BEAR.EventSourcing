<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\EventSourcing\Query\SearchQueryInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

use function array_filter;
use function ceil;

/**
 * Search resource (検索)
 */
class Search extends ResourceObject
{
    #[Inject]
    public function __construct(private SearchQueryInterface $searchQuery)
    {
    }

    /**
     * Search products
     *
     * @param string|null $q          Search keyword
     * @param int|null    $categoryId Category filter
     * @param string|null $priceMin   Minimum price
     * @param string|null $priceMax   Maximum price
     * @param bool|null   $inStock    In stock only
     * @param string      $sort       Sort field (name, price, date)
     * @param string      $order      Sort order (asc, desc)
     * @param int         $page       Page number
     * @param int         $limit      Items per page
     */
    public function onGet(
        string|null $q = null,
        int|null $categoryId = null,
        string|null $priceMin = null,
        string|null $priceMax = null,
        bool|null $inStock = null,
        string $sort = 'date',
        string $order = 'desc',
        int $page = 1,
        int $limit = 20,
    ): static {
        $offset = ($page - 1) * $limit;

        $filters = [
            'keyword' => $q,
            'category_id' => $categoryId,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'in_stock' => $inStock,
        ];

        $results = $this->searchQuery->searchProducts($filters, $sort, $order, $limit, $offset);
        $total = $this->searchQuery->countProducts($filters);

        $this->body = [
            'query' => $q,
            'filters' => array_filter($filters, static fn ($v) => $v !== null),
            'products' => $results,
            'facets' => $this->searchQuery->getFacets($filters),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ];

        return $this;
    }
}
