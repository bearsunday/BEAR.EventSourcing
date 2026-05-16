<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Cart entity (カート)
 */
class Cart extends AbstractEntity
{
    protected ?int $id = null;
    protected ?string $cartKey = null;
    protected ?int $customerId = null;
    protected ?Customer $customer = null;
    protected ?string $preOrderId = null;
    /** @var CartItem[] */
    protected array $cartItems = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getCartKey(): ?string
    {
        return $this->cartKey;
    }

    public function setCartKey(?string $cartKey): static
    {
        $this->cartKey = $cartKey;
        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): static
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    public function getPreOrderId(): ?string
    {
        return $this->preOrderId;
    }

    public function setPreOrderId(?string $preOrderId): static
    {
        $this->preOrderId = $preOrderId;
        return $this;
    }

    /**
     * @return CartItem[]
     */
    public function getCartItems(): array
    {
        return $this->cartItems;
    }

    /**
     * @param CartItem[] $cartItems
     */
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
    public function findCartItem(int $productClassId): ?CartItem
    {
        foreach ($this->cartItems as $item) {
            if ($item->getProductClassId() === $productClassId) {
                return $item;
            }
        }
        return null;
    }
}
