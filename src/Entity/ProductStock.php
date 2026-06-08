<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Product stock entity (商品在庫)
 */
class ProductStock extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $productClassId = null;
    protected ProductClass|null $productClass = null;
    protected int|null $stock = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getProductClassId(): int|null
    {
        return $this->productClassId;
    }

    public function setProductClassId(int|null $productClassId): static
    {
        $this->productClassId = $productClassId;

        return $this;
    }

    public function getProductClass(): ProductClass|null
    {
        return $this->productClass;
    }

    public function setProductClass(ProductClass|null $productClass): static
    {
        $this->productClass = $productClass;

        return $this;
    }

    public function getStock(): int|null
    {
        return $this->stock;
    }

    public function setStock(int|null $stock): static
    {
        $this->stock = $stock;

        return $this;
    }
}
