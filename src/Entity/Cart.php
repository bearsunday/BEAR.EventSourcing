<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use function bcadd;

/**
 * Cart entity (カート)
 */
class Cart extends AbstractEntity
{
    protected int|null $id = null;
    protected string|null $cartKey = null;
    protected int|null $customerId = null;
    protected Customer|null $customer = null;
    protected string|null $preOrderId = null;
    /** @var CartItem[] */
    protected array $cartItems = [];

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCartKey(): string|null
    {
        return $this->cartKey;
    }

    public function setCartKey(string|null $cartKey): static
    {
        $this->cartKey = $cartKey;

        return $this;
    }

    public function getCustomerId(): int|null
    {
        return $this->customerId;
    }

    public function setCustomerId(int|null $customerId): static
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getCustomer(): Customer|null
    {
        return $this->customer;
    }

    public function setCustomer(Customer|null $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPreOrderId(): string|null
    {
        return $this->preOrderId;
    }

    public function setPreOrderId(string|null $preOrderId): static
    {
        $this->preOrderId = $preOrderId;

        return $this;
    }

    /** @return CartItem[] */
    public function getCartItems(): array
    {
        return $this->cartItems;
    }

    /** @param CartItem[] $cartItems */
    public function setCartItems(array $cartItems): static
    {
        $this->cartItems = $cartItems;

        return $this;
    }

    public function addCartItem(CartItem $cartItem): static
    {
        $this->cartItems[] = $cartItem;

        return $this;
    }

    /**
     * Get total quantity
     */
    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->cartItems as $item) {
            $total += $item->getQuantity();
        }

        return $total;
    }

    /**
     * Get total price
     */
    public function getTotalPrice(): string
    {
        $total = '0';
        foreach ($this->cartItems as $item) {
            $total = bcadd($total, $item->getTotalPrice());
        }

        return $total;
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->cartItems);
    }

    /**
     * Find cart item by product class id
     */
    public function findCartItem(int $productClassId): CartItem|null
    {
        foreach ($this->cartItems as $item) {
            if ($item->getProductClassId() === $productClassId) {
                return $item;
            }
        }

        return null;
    }
}
