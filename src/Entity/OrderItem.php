<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\OrderItemType;
use BEAR\EventSourcing\Entity\Master\TaxDisplayType;
use BEAR\EventSourcing\Entity\Master\TaxType;

/**
 * Order item entity (注文明細)
 */
class OrderItem extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $orderId = null;
    protected ?Order $order = null;
    protected ?int $productId = null;
    protected ?Product $product = null;
    protected ?int $productClassId = null;
    protected ?ProductClass $productClass = null;
    protected ?int $shippingId = null;
    protected ?Shipping $shipping = null;
    protected ?OrderItemType $orderItemType = null;
    protected ?TaxType $taxType = null;
    protected ?TaxDisplayType $taxDisplayType = null;
    protected string $productName = '';
    protected ?string $productCode = null;
    protected ?string $className1 = null;
    protected ?string $className2 = null;
    protected ?string $classCategoryName1 = null;
    protected ?string $classCategoryName2 = null;
    protected string $price = '0';
    protected string $quantity = '0';
    protected string $tax = '0';
    protected string $taxRate = '0';
    protected string $taxRuleId = '0';
    protected int $pointRate = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(?int $orderId): static
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
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

    public function getShippingId(): ?int
    {
        return $this->shippingId;
    }

    public function setShippingId(?int $shippingId): static
    {
        $this->shippingId = $shippingId;
        return $this;
    }

    public function getShipping(): ?Shipping
    {
        return $this->shipping;
    }

    public function setShipping(?Shipping $shipping): static
    {
        $this->shipping = $shipping;
        return $this;
    }

    public function getOrderItemType(): ?OrderItemType
    {
        return $this->orderItemType;
    }

    public function setOrderItemType(?OrderItemType $orderItemType): static
    {
        $this->orderItemType = $orderItemType;
        return $this;
    }

    public function getTaxType(): ?TaxType
    {
        return $this->taxType;
    }

    public function setTaxType(?TaxType $taxType): static
    {
        $this->taxType = $taxType;
        return $this;
    }

    public function getTaxDisplayType(): ?TaxDisplayType
    {
        return $this->taxDisplayType;
    }

    public function setTaxDisplayType(?TaxDisplayType $taxDisplayType): static
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

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(?string $productCode): static
    {
        $this->productCode = $productCode;
        return $this;
    }

    public function getClassName1(): ?string
    {
        return $this->className1;
    }

    public function setClassName1(?string $className1): static
    {
        $this->className1 = $className1;
        return $this;
    }

    public function getClassName2(): ?string
    {
        return $this->className2;
    }

    public function setClassName2(?string $className2): static
    {
        $this->className2 = $className2;
        return $this;
    }

    public function getClassCategoryName1(): ?string
    {
        return $this->classCategoryName1;
    }

    public function setClassCategoryName1(?string $classCategoryName1): static
    {
        $this->classCategoryName1 = $classCategoryName1;
        return $this;
    }

    public function getClassCategoryName2(): ?string
    {
        return $this->classCategoryName2;
    }

    public function setClassCategoryName2(?string $classCategoryName2): static
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
