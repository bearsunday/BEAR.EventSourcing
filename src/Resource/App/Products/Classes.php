<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Products;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Query\ProductClassQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Product classes resource (商品規格一覧)
 */
class Classes extends ResourceObject
{
    private ProductClassQueryInterface $productClassQuery;

    #[Inject]
    public function __construct(ProductClassQueryInterface $productClassQuery)
    {
        $this->productClassQuery = $productClassQuery;
    }

    /**
     * Get product classes by product ID
     *
     * @param int $id Product ID
     */
    public function onGet(int $id): static
    {
        $this->body = $this->productClassQuery->findByProductId($id);
        return $this;
    }

    /**
     * Create a new product class
     *
     * @param int         $id             Product ID
     * @param string      $code           Product code
     * @param string      $price02        Selling price
     * @param string|null $price01        Regular price
     * @param int|null    $stock          Stock quantity
     * @param bool        $stockUnlimited Unlimited stock flag
     * @param int|null    $classCategory1 Class category 1 ID
     * @param int|null    $classCategory2 Class category 2 ID
     */
    public function onPost(
        int $id,
        string $code,
        string $price02,
        ?string $price01 = null,
        ?int $stock = null,
        bool $stockUnlimited = false,
        ?int $classCategory1 = null,
        ?int $classCategory2 = null
    ): static {
        $classId = $this->productClassQuery->create([
            'product_id' => $id,
            'code' => $code,
            'price02' => $price02,
            'price01' => $price01,
            'stock' => $stock,
            'stock_unlimited' => $stockUnlimited,
            'class_category_id1' => $classCategory1,
            'class_category_id2' => $classCategory2,
        ]);

        $this->code = 201;
        $this->body = ['id' => $classId];

        return $this;
    }
}
