<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\ProductCategoryQueryInterface;
use BEAR\EventSourcing\Query\ProductClassQueryInterface;
use BEAR\EventSourcing\Query\ProductImageQueryInterface;
use BEAR\EventSourcing\Query\ProductQueryInterface;
use BEAR\Resource\ResourceObject;

use function array_merge;

class Products extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery,
        private readonly ProductClassQueryInterface $productClassQuery,
        private readonly ProductImageQueryInterface $productImageQuery,
        private readonly ProductCategoryQueryInterface $productCategoryQuery,
    ) {
    }

    #[RequireAuth(role: 'admin')]
    public function onGet(
        int|null $id = null,
        string|null $name = null,
        int|null $product_status_id = null,
        int|null $category_id = null,
        int $limit = 20,
        int $offset = 0,
    ): static {
        if ($id !== null) {
            $product = $this->productQuery->findById($id);
            if ($product === null) {
                $this->code = 404;
                $this->body = ['error' => 'Product not found'];

                return $this;
            }

            $product['classes'] = $this->productClassQuery->findByProductId($id);
            $product['images'] = $this->productImageQuery->findByProductId($id);
            $product['categories'] = $this->productCategoryQuery->findByProductId($id);
            $this->body = $product;
        } else {
            $filters = [];
            if ($name !== null) {
                $filters['name'] = $name;
            }

            if ($product_status_id !== null) {
                $filters['product_status_id'] = $product_status_id;
            }

            if ($category_id !== null) {
                $filters['category_id'] = $category_id;
            }

            $products = $this->productQuery->findByFilters($filters, $limit, $offset);
            $total = $this->productQuery->countByFilters($filters);

            $this->body = [
                'products' => $products,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ];
        }

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPost(
        string $name,
        int $product_status_id = 2,
        string|null $description_list = null,
        string|null $description_detail = null,
        string|null $search_word = null,
        string|null $note = null,
        array|null $category_ids = null,
        array|null $classes = null,
    ): static {
        $productId = $this->productQuery->create([
            'name' => $name,
            'product_status_id' => $product_status_id,
            'description_list' => $description_list,
            'description_detail' => $description_detail,
            'search_word' => $search_word,
            'note' => $note,
        ]);

        // Add categories
        if ($category_ids !== null) {
            foreach ($category_ids as $categoryId) {
                $this->productCategoryQuery->create($productId, (int) $categoryId);
            }
        }

        // Add product classes
        if ($classes !== null) {
            foreach ($classes as $class) {
                $this->productClassQuery->create(array_merge(
                    ['product_id' => $productId],
                    $class,
                ));
            }
        } else {
            // Create default class
            $this->productClassQuery->create([
                'product_id' => $productId,
                'sale_type_id' => 1,
                'visible' => 1,
            ]);
        }

        $this->code = 201;
        $this->body = ['id' => $productId];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(
        int $id,
        string|null $name = null,
        int|null $product_status_id = null,
        string|null $description_list = null,
        string|null $description_detail = null,
        string|null $search_word = null,
        string|null $note = null,
    ): static {
        $product = $this->productQuery->findById($id);
        if ($product === null) {
            $this->code = 404;
            $this->body = ['error' => 'Product not found'];

            return $this;
        }

        $data = [];
        if ($name !== null) {
            $data['name'] = $name;
        }

        if ($product_status_id !== null) {
            $data['product_status_id'] = $product_status_id;
        }

        if ($description_list !== null) {
            $data['description_list'] = $description_list;
        }

        if ($description_detail !== null) {
            $data['description_detail'] = $description_detail;
        }

        if ($search_word !== null) {
            $data['search_word'] = $search_word;
        }

        if ($note !== null) {
            $data['note'] = $note;
        }

        if (! empty($data)) {
            $this->productQuery->update($id, $data);
        }

        $this->code = 200;
        $this->body = ['id' => $id, 'updated' => true];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onDelete(int $id): static
    {
        $product = $this->productQuery->findById($id);
        if ($product === null) {
            $this->code = 404;
            $this->body = ['error' => 'Product not found'];

            return $this;
        }

        $this->productQuery->delete($id);

        $this->code = 200;
        $this->body = ['deleted' => true];

        return $this;
    }
}
