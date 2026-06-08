<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Product image entity (商品画像)
 */
class ProductImage extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $productId = null;
    protected Product|null $product = null;
    protected string $fileName = '';
    protected int $sortNo = 0;

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

    public function getProduct(): Product|null
    {
        return $this->product;
    }

    public function setProduct(Product|null $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getSortNo(): int
    {
        return $this->sortNo;
    }

    public function setSortNo(int $sortNo): static
    {
        $this->sortNo = $sortNo;

        return $this;
    }
}
