<?php

declare(strict_types=1);

namespace BearEccube\Entity;

use BearEccube\Entity\Master\SaleType;

/**
 * Product class entity (商品規格)
 */
class ProductClass extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $productId = null;
    protected ?Product $product = null;
    protected ?SaleType $saleType = null;
    protected ?ClassCategory $classCategory1 = null;
    protected ?ClassCategory $classCategory2 = null;
    protected string $code = '';
    protected ?int $stock = null;
    protected bool $stockUnlimited = false;
    protected ?string $price01 = null; // 通常価格
    protected ?string $price02 = null; // 販売価格
    protected ?string $deliveryFee = null;
    protected bool $visible = true;
    protected ?ProductStock $productStock = null;

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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getSaleType(): ?SaleType
    {
        return $this->saleType;
    }

    public function setSaleType(?SaleType $saleType): static
    {
        $this->saleType = $saleType;
        return $this;
    }

    public function getClassCategory1(): ?ClassCategory
    {
        return $this->classCategory1;
    }

    public function setClassCategory1(?ClassCategory $classCategory1): static
    {
        $this->classCategory1 = $classCategory1;
        return $this;
    }

    public function getClassCategory2(): ?ClassCategory
    {
        return $this->classCategory2;
    }

    public function setClassCategory2(?ClassCategory $classCategory2): static
    {
        $this->classCategory2 = $classCategory2;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function isStockUnlimited(): bool
    {
        return $this->stockUnlimited;
    }

    public function setStockUnlimited(bool $stockUnlimited): static
    {
        $this->stockUnlimited = $stockUnlimited;
        return $this;
    }

    public function getPrice01(): ?string
    {
        return $this->price01;
    }

    public function setPrice01(?string $price01): static
    {
        $this->price01 = $price01;
        return $this;
    }

    public function getPrice02(): ?string
    {
        return $this->price02;
    }

    public function setPrice02(?string $price02): static
    {
        $this->price02 = $price02;
        return $this;
    }

    public function getDeliveryFee(): ?string
    {
        return $this->deliveryFee;
    }

    public function setDeliveryFee(?string $deliveryFee): static
    {
        $this->deliveryFee = $deliveryFee;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function getProductStock(): ?ProductStock
    {
        return $this->productStock;
    }

    public function setProductStock(?ProductStock $productStock): static
    {
        $this->productStock = $productStock;
        return $this;
    }

    /**
     * Check if this product class has stock
     */
    public function isStock(): bool
    {
        if ($this->stockUnlimited) {
            return true;
        }
        return $this->stock !== null && $this->stock > 0;
    }

    /**
     * Get formatted class name (e.g., "サイズ:L / 色:赤")
     */
    public function getClassName(): string
    {
        $parts = [];
        if ($this->classCategory1 !== null) {
            $parts[] = $this->classCategory1->getName();
        }
        if ($this->classCategory2 !== null) {
            $parts[] = $this->classCategory2->getName();
        }
        return implode(' / ', $parts);
    }
}
