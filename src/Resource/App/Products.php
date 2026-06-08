<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\EventSourcing\Query\ProductQueryInterface;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

/**
 * Products resource (商品一覧)
 *
 * @Link(rel="product", href="/products/{id}")
 */
class Products extends ResourceObject
{
    #[Inject]
    public function __construct(private ProductQueryInterface $productQuery)
    {
    }

    /**
     * Get product list
     *
     * @param int|null    $categoryId Category ID to filter
     * @param string|null $name       Product name to search
     * @param int         $page       Page number
     * @param int         $limit      Items per page
     */
    public function onGet(
        int|null $categoryId = null,
        string|null $name = null,
        int $page = 1,
        int $limit = 20,
    ): static {
        $offset = ($page - 1) * $limit;

        $this->body = [
            'products' => $this->productQuery->findAll($categoryId, $name, $limit, $offset),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $this->productQuery->count($categoryId, $name),
            ],
        ];

        return $this;
    }

    /**
     * Create a new product
     *
     * @param string      $name              Product name
     * @param int         $status            Product status ID
     * @param string|null $descriptionList   Short description
     * @param string|null $descriptionDetail Full description
     * @param string|null $searchWord        Search keywords
     * @param string|null $note              Admin note
     */
    public function onPost(
        string $name,
        int $status,
        string|null $descriptionList = null,
        string|null $descriptionDetail = null,
        string|null $searchWord = null,
        string|null $note = null,
    ): static {
        $id = $this->productQuery->create([
            'name' => $name,
            'status_id' => $status,
            'description_list' => $descriptionList,
            'description_detail' => $descriptionDetail,
            'search_word' => $searchWord,
            'note' => $note,
        ]);

        $this->code = 201;
        $this->headers['Location'] = "/products/{$id}";
        $this->body = ['id' => $id];

        return $this;
    }
}
