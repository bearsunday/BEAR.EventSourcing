<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Product category relation entity (商品カテゴリ関連)
 */
class ProductCategory extends AbstractEntity
{
    protected ?int $productId = null;
    protected ?int $categoryId = null;
    protected ?Product $product = null;
    protected ?Category $category = null;

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): static
    {
        $this->productId = $productId;
        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): static
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }
}
