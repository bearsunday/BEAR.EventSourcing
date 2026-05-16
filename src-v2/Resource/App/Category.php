<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\CategoryQueryInterface;

class Category extends ResourceObject
{
    public function __construct(
        private readonly CategoryQueryInterface $query
    ) {
    }

    #[Link(rel: 'collection', href: '/categories')]
    #[Link(rel: 'products', href: '/products{?category_id}')]
    #[JsonSchema('category.get.json')]
    public function onGet(int $id): static
    {
        $category = $this->query->findById($id);

        if ($category === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => 'Category not found'];
            return $this;
        }

        $this->body = $category;
        return $this;
    }
}
