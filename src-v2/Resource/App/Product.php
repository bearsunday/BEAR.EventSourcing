<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\ProductQueryInterface;

/**
 * Product Resource (Single Item)
 */
class Product extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery
    ) {}

    /**
     * 商品詳細
     */
    public function onGet(int $id): static
    {
        $product = $this->productQuery->findById($id);

        if ($product === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => 'Product not found'];
            return $this;
        }

        $this->body = $product;

        return $this;
    }
}
