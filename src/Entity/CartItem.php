<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Cart item entity (カート明細)
 */
class CartItem extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $cartId = null;
    protected ?Cart $cart = null;
    protected ?int $productClassId = null;
    protected ?ProductClass $productClass = null;
    protected int $quantity = 0;
    protected string $price = '0';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getCartId(): ?int
    {
        return $this->cartId;
    }

    public function setCartId(?int $cartId): static
    {
        $this->cartId = $cartId;
        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;
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

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
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

    /**
     * Get total price (price * quantity)
     */
    public function getTotalPrice(): string
    {
        return bcmul($this->price, (string)$this->quantity);
    }

    /**
     * Get product name from product class
     */
    public function getProductName(): string
    {
        return $this->productClass?->getProduct()?->getName() ?? '';
    }

    /**
     * Get class name from product class
     */
    public function getClassName(): string
    {
        return $this->productClass?->getClassName() ?? '';
    }
}
