<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Product category relation entity (商品カテゴリ関連)
 */
class ProductCategory extends AbstractEntity
{
    protected int|null $productId = null;
    protected int|null $categoryId = null;
    protected Product|null $product = null;
    protected Category|null $category = null;

    public function getProductId(): int|null
    {
        return $this->productId;
    }

    public function setProductId(int|null $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function getCategoryId(): int|null
    {
        return $this->categoryId;
    }

    public function setCategoryId(int|null $categoryId): static
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getProduct(): Product|null
    {
        return $this->product;
    }

    public function setProduct(Product|null $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getCategory(): Category|null
    {
        return $this->category;
    }

    public function setCategory(Category|null $category): static
    {
        $this->category = $category;

        return $this;
    }
}
