<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\SaleType;

use function implode;

/**
 * Product class entity (商品規格)
 */
class ProductClass extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $productId = null;
    protected Product|null $product = null;
    protected SaleType|null $saleType = null;
    protected ClassCategory|null $classCategory1 = null;
    protected ClassCategory|null $classCategory2 = null;
    protected string $code = '';
    protected int|null $stock = null;
    protected bool $stockUnlimited = false;
    protected string|null $price01 = null; // 通常価格
    protected string|null $price02 = null; // 販売価格
    protected string|null $deliveryFee = null;
    protected bool $visible = true;
    protected ProductStock|null $productStock = null;

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

    public function getSaleType(): SaleType|null
    {
        return $this->saleType;
    }

    public function setSaleType(SaleType|null $saleType): static
    {
        $this->saleType = $saleType;

        return $this;
    }

    public function getClassCategory1(): ClassCategory|null
    {
        return $this->classCategory1;
    }

    public function setClassCategory1(ClassCategory|null $classCategory1): static
    {
        $this->classCategory1 = $classCategory1;

        return $this;
    }

    public function getClassCategory2(): ClassCategory|null
    {
        return $this->classCategory2;
    }

    public function setClassCategory2(ClassCategory|null $classCategory2): static
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

    public function getStock(): int|null
    {
        return $this->stock;
    }

    public function setStock(int|null $stock): static
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

    public function getPrice01(): string|null
    {
        return $this->price01;
    }

    public function setPrice01(string|null $price01): static
    {
        $this->price01 = $price01;

        return $this;
    }

    public function getPrice02(): string|null
    {
        return $this->price02;
    }

    public function setPrice02(string|null $price02): static
    {
        $this->price02 = $price02;

        return $this;
    }

    public function getDeliveryFee(): string|null
    {
        return $this->deliveryFee;
    }

    public function setDeliveryFee(string|null $deliveryFee): static
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

    public function getProductStock(): ProductStock|null
    {
        return $this->productStock;
    }

    public function setProductStock(ProductStock|null $productStock): static
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
