<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Product stock entity (商品在庫)
 */
class ProductStock extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $productClassId = null;
    protected ?ProductClass $productClass = null;
    protected ?int $stock = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getProductClassId(): ?int
    {
        return $this->productClassId;
    }

    public function setProductClassId(?int $productClassId): static
    {
        $this->productClassId = $productClassId;
        return $this;
    }

    public function getProductClass(): ?ProductClass
    {
        return $this->productClass;
    }

    public function setProductClass(?ProductClass $productClass): static
    {
        $this->productClass = $productClass;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;
        return $this;
    }
}
