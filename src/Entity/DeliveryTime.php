<?php

declare(strict_types=1);

namespace BearEccube\Entity;

/**
 * Delivery time entity (お届け時間)
 */
class DeliveryTime extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $deliveryId = null;
    protected ?Delivery $delivery = null;
    protected string $deliveryTime = '';
    protected int $sortNo = 0;
    protected bool $visible = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getDeliveryId(): ?int
    {
        return $this->deliveryId;
    }

    public function setDeliveryId(?int $deliveryId): static
    {
        $this->deliveryId = $deliveryId;
        return $this;
    }

    public function getDelivery(): ?Delivery
    {
        return $this->delivery;
    }

    public function setDelivery(?Delivery $delivery): static
    {
        $this->delivery = $delivery;
        return $this;
    }

    public function getDeliveryTime(): string
    {
        return $this->deliveryTime;
    }

    public function setDeliveryTime(string $deliveryTime): static
    {
        $this->deliveryTime = $deliveryTime;
        return $this;
    }

    public function getSortNo(): int
    {
        return $this->sortNo;
    }

    public function setSortNo(int $sortNo): static
    {
        $this->sortNo = $sortNo;
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
}
