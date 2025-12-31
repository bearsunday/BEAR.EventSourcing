<?php

declare(strict_types=1);

namespace BearEccube\Entity;

/**
 * Product tag relation entity (商品タグ関連)
 */
class ProductTag extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $productId = null;
    protected ?int $tagId = null;
    protected ?Product $product = null;
    protected ?Tag $tag = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): static
    {
        $this->productId = $productId;
        return $this;
    }

    public function getTagId(): ?int
    {
        return $this->tagId;
    }

    public function setTagId(?int $tagId): static
    {
        $this->tagId = $tagId;
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

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): static
    {
        $this->tag = $tag;
        return $this;
    }
}
