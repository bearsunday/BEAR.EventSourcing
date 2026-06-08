<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Product tag relation entity (商品タグ関連)
 */
class ProductTag extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $productId = null;
    protected int|null $tagId = null;
    protected Product|null $product = null;
    protected Tag|null $tag = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getProductId(): int|null
    {
        return $this->productId;
    }

    public function setProductId(int|null $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function getTagId(): int|null
    {
        return $this->tagId;
    }

    public function setTagId(int|null $tagId): static
    {
        $this->tagId = $tagId;

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

    public function getTag(): Tag|null
    {
        return $this->tag;
    }

    public function setTag(Tag|null $tag): static
    {
        $this->tag = $tag;

        return $this;
    }
}
