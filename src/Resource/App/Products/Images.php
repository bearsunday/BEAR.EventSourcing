<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Products;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Query\ProductImageQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Product images resource (商品画像一覧)
 */
class Images extends ResourceObject
{
    private ProductImageQueryInterface $productImageQuery;

    #[Inject]
    public function __construct(ProductImageQueryInterface $productImageQuery)
    {
        $this->productImageQuery = $productImageQuery;
    }

    /**
     * Get product images by product ID
     *
     * @param int $id Product ID
     */
    public function onGet(int $id): static
    {
        $this->body = $this->productImageQuery->findByProductId($id);
        return $this;
    }

    /**
     * Add a product image
     *
     * @param int    $id       Product ID
     * @param string $fileName Image file name
     * @param int    $sortNo   Sort order
     */
    public function onPost(int $id, string $fileName, int $sortNo = 0): static
    {
        $imageId = $this->productImageQuery->create([
            'product_id' => $id,
            'file_name' => $fileName,
            'sort_no' => $sortNo,
        ]);

        $this->code = 201;
        $this->body = ['id' => $imageId];

        return $this;
    }
}
