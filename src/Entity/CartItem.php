<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use function bcmul;

/**
 * Cart item entity (カート明細)
 */
class CartItem extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $cartId = null;
    protected Cart|null $cart = null;
    protected int|null $productClassId = null;
    protected ProductClass|null $productClass = null;
    protected int $quantity = 0;
    protected string $price = '0';

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCartId(): int|null
    {
        return $this->cartId;
    }

    public function setCartId(int|null $cartId): static
    {
        $this->cartId = $cartId;

        return $this;
    }

    public function getCart(): Cart|null
    {
        return $this->cart;
    }

    public function setCart(Cart|null $cart): static
    {
        $this->cart = $cart;

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
        return bcmul($this->price, (string) $this->quantity);
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
