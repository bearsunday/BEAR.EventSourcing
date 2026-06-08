<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Customer favorite product entity (お気に入り商品)
 */
class CustomerFavoriteProduct extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $customerId = null;
    protected Customer|null $customer = null;
    protected int|null $productId = null;
    protected Product|null $product = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

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
}
