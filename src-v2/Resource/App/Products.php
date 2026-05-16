<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\ProductQueryInterface;

class Products extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery
    ) {
    }

    #[Link(rel: 'item', href: '/product{?id}')]
    #[JsonSchema('products.get.json')]
    public function onGet(
        ?string $name = null,
        ?int $category_id = null,
        ?int $status_id = null,
        int $limit = 20,
        int $offset = 0
    ): static {
        $this->body = $this->productQuery->findList(
            $name,
            $category_id,
            $status_id,
            $limit,
            $offset
        );

        return $this;
    }
}
