<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\OrderItemType;
use BEAR\EventSourcing\Entity\Master\TaxDisplayType;
use BEAR\EventSourcing\Entity\Master\TaxType;

use function bcmul;

/**
 * Order item entity (注文明細)
 */
class OrderItem extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $orderId = null;
    protected Order|null $order = null;
    protected int|null $productId = null;
    protected Product|null $product = null;
    protected int|null $productClassId = null;
    protected ProductClass|null $productClass = null;
    protected int|null $shippingId = null;
    protected Shipping|null $shipping = null;
    protected OrderItemType|null $orderItemType = null;
    protected TaxType|null $taxType = null;
    protected TaxDisplayType|null $taxDisplayType = null;
    protected string $productName = '';
    protected string|null $productCode = null;
    protected string|null $className1 = null;
    protected string|null $className2 = null;
    protected string|null $classCategoryName1 = null;
    protected string|null $classCategoryName2 = null;
    protected string $price = '0';
    protected string $quantity = '0';
    protected string $tax = '0';
    protected string $taxRate = '0';
    protected string $taxRuleId = '0';
    protected int $pointRate = 0;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getOrderId(): int|null
    {
        return $this->orderId;
    }

    public function setOrderId(int|null $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getOrder(): Order|null
    {
        return $this->order;
    }

    public function setOrder(Order|null $order): static
    {
        $this->order = $order;

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

    public function getShippingId(): int|null
    {
        return $this->shippingId;
    }

    public function setShippingId(int|null $shippingId): static
    {
        $this->shippingId = $shippingId;

        return $this;
    }

    public function getShipping(): Shipping|null
    {
        return $this->shipping;
    }

    public function setShipping(Shipping|null $shipping): static
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function getOrderItemType(): OrderItemType|null
    {
        return $this->orderItemType;
    }

    public function setOrderItemType(OrderItemType|null $orderItemType): static
    {
        $this->orderItemType = $orderItemType;

        return $this;
    }

    public function getTaxType(): TaxType|null
    {
        return $this->taxType;
    }

    public function setTaxType(TaxType|null $taxType): static
    {
        $this->taxType = $taxType;

        return $this;
    }

    public function getTaxDisplayType(): TaxDisplayType|null
    {
        return $this->taxDisplayType;
    }

    public function setTaxDisplayType(TaxDisplayType|null $taxDisplayType): static
    {
        $this->taxDisplayType = $taxDisplayType;

        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getProductCode(): string|null
    {
        return $this->productCode;
    }

    public function setProductCode(string|null $productCode): static
    {
        $this->productCode = $productCode;

        return $this;
    }

    public function getClassName1(): string|null
    {
        return $this->className1;
    }

    public function setClassName1(string|null $className1): static
    {
        $this->className1 = $className1;

        return $this;
    }

    public function getClassName2(): string|null
    {
        return $this->className2;
    }

    public function setClassName2(string|null $className2): static
    {
        $this->className2 = $className2;

        return $this;
    }

    public function getClassCategoryName1(): string|null
    {
        return $this->classCategoryName1;
    }

    public function setClassCategoryName1(string|null $classCategoryName1): static
    {
        $this->classCategoryName1 = $classCategoryName1;

        return $this;
    }

    public function getClassCategoryName2(): string|null
    {
        return $this->classCategoryName2;
    }

    public function setClassCategoryName2(string|null $classCategoryName2): static
    {
        $this->classCategoryName2 = $classCategoryName2;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getTax(): string
    {
        return $this->tax;
    }

    public function setTax(string $tax): static
    {
        $this->tax = $tax;

        return $this;
    }

    public function getTaxRate(): string
    {
        return $this->taxRate;
    }

    public function setTaxRate(string $taxRate): static
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    public function getTaxRuleId(): string
    {
        return $this->taxRuleId;
    }

    public function setTaxRuleId(string $taxRuleId): static
    {
        $this->taxRuleId = $taxRuleId;

        return $this;
    }

    public function getPointRate(): int
    {
        return $this->pointRate;
    }

    public function setPointRate(int $pointRate): static
    {
        $this->pointRate = $pointRate;

        return $this;
    }

    /**
     * Get total price (price * quantity)
     */
    public function getTotalPrice(): string
    {
        return bcmul($this->price, $this->quantity);
    }

    /**
     * Check if this item is a product (not delivery fee, charge, etc.)
     */
    public function isProduct(): bool
    {
        return $this->productId !== null;
    }
}
