<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Query\ProductQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Products resource (商品一覧)
 *
 * @Link(rel="product", href="/products/{id}")
 */
class Products extends ResourceObject
{
    private ProductQueryInterface $productQuery;

    #[Inject]
    public function __construct(ProductQueryInterface $productQuery)
    {
        $this->productQuery = $productQuery;
    }

    /**
     * Get product list
     *
     * @param int|null    $categoryId  Category ID to filter
     * @param string|null $name        Product name to search
     * @param int         $page        Page number
     * @param int         $limit       Items per page
     */
    public function onGet(
        ?int $categoryId = null,
        ?string $name = null,
        int $page = 1,
        int $limit = 20
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
        ?string $descriptionList = null,
        ?string $descriptionDetail = null,
        ?string $searchWord = null,
        ?string $note = null
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
