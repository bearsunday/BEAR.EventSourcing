<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Query\CategoryQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Categories resource (カテゴリ一覧)
 *
 * @Link(rel="category", href="/categories/{id}")
 */
class Categories extends ResourceObject
{
    private CategoryQueryInterface $categoryQuery;

    #[Inject]
    public function __construct(CategoryQueryInterface $categoryQuery)
    {
        $this->categoryQuery = $categoryQuery;
    }

    /**
     * Get category tree
     *
     * @param int|null $parentId Parent category ID (null for root categories)
     * @param bool     $tree     Return as tree structure
     */
    public function onGet(?int $parentId = null, bool $tree = false): static
    {
        if ($tree) {
            $this->body = $this->categoryQuery->getTree();
        } else {
            $this->body = $this->categoryQuery->findByParentId($parentId);
        }

        return $this;
    }

    /**
     * Create a new category
     *
     * @param string   $name     Category name
     * @param int|null $parentId Parent category ID
     * @param int      $sortNo   Sort order
     */
    public function onPost(string $name, ?int $parentId = null, int $sortNo = 0): static
    {
        $id = $this->categoryQuery->create([
            'name' => $name,
            'parent_id' => $parentId,
            'sort_no' => $sortNo,
        ]);

        $this->code = 201;
        $this->headers['Location'] = "/categories/{$id}";
        $this->body = ['id' => $id];

        return $this;
    }
}
