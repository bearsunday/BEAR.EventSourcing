<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Products;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Query\ProductCategoryQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Product categories resource (商品カテゴリ関連)
 */
class Categories extends ResourceObject
{
    private ProductCategoryQueryInterface $productCategoryQuery;

    #[Inject]
    public function __construct(ProductCategoryQueryInterface $productCategoryQuery)
    {
        $this->productCategoryQuery = $productCategoryQuery;
    }

    /**
     * Get categories for a product
     *
     * @param int $id Product ID
     */
    public function onGet(int $id): static
    {
        $this->body = $this->productCategoryQuery->findByProductId($id);
        return $this;
    }

    /**
     * Add product to category
     *
     * @param int $id         Product ID
     * @param int $categoryId Category ID
     */
    public function onPost(int $id, int $categoryId): static
    {
        $this->productCategoryQuery->addCategory($id, $categoryId);

        $this->code = 201;
        $this->body = ['product_id' => $id, 'category_id' => $categoryId];

        return $this;
    }

    /**
     * Remove product from category
     *
     * @param int $id         Product ID
     * @param int $categoryId Category ID
     */
    public function onDelete(int $id, int $categoryId): static
    {
        $this->productCategoryQuery->removeCategory($id, $categoryId);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
