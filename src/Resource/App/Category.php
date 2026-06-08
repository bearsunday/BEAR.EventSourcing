<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\EventSourcing\Query\CategoryQueryInterface;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

use function array_filter;

/**
 * Category resource (カテゴリ詳細)
 *
 * @Link(rel="products", href="/products?categoryId={id}")
 * @Link(rel="children", href="/categories?parentId={id}")
 * @Link(rel="parent", href="/categories/{parent_id}")
 */
class Category extends ResourceObject
{
    #[Inject]
    public function __construct(private CategoryQueryInterface $categoryQuery)
    {
    }

    /**
     * Get category by ID
     *
     * @param int $id Category ID
     */
    public function onGet(int $id): static
    {
        $category = $this->categoryQuery->findById($id);

        if ($category === null) {
            $this->code = 404;
            $this->body = ['error' => 'Category not found'];

            return $this;
        }

        $this->body = $category;

        return $this;
    }

    /**
     * Update category
     *
     * @param int         $id       Category ID
     * @param string|null $name     Category name
     * @param int|null    $parentId Parent category ID
     * @param int|null    $sortNo   Sort order
     */
    public function onPut(
        int $id,
        string|null $name = null,
        int|null $parentId = null,
        int|null $sortNo = null,
    ): static {
        $category = $this->categoryQuery->findById($id);

        if ($category === null) {
            $this->code = 404;
            $this->body = ['error' => 'Category not found'];

            return $this;
        }

        $data = array_filter([
            'name' => $name,
            'parent_id' => $parentId,
            'sort_no' => $sortNo,
        ], static fn ($v) => $v !== null);

        $this->categoryQuery->update($id, $data);

        $this->body = $this->categoryQuery->findById($id);

        return $this;
    }

    /**
     * Delete category
     *
     * @param int $id Category ID
     */
    public function onDelete(int $id): static
    {
        $category = $this->categoryQuery->findById($id);

        if ($category === null) {
            $this->code = 404;
            $this->body = ['error' => 'Category not found'];

            return $this;
        }

        // Check if category has children
        $children = $this->categoryQuery->findByParentId($id);
        if (! empty($children)) {
            $this->code = 400;
            $this->body = ['error' => 'Cannot delete category with children'];

            return $this;
        }

        $this->categoryQuery->delete($id);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
