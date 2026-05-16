<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\ProductQueryInterface;

class Product extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery
    ) {
    }

    #[Link(rel: 'collection', href: '/products')]
    #[JsonSchema('product.get.json')]
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
