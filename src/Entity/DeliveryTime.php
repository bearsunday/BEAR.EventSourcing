<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Delivery time entity (お届け時間)
 */
class DeliveryTime extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $deliveryId = null;
    protected Delivery|null $delivery = null;
    protected string $deliveryTime = '';
    protected int $sortNo = 0;
    protected bool $visible = true;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getDeliveryId(): int|null
    {
        return $this->deliveryId;
    }

    public function setDeliveryId(int|null $deliveryId): static
    {
        $this->deliveryId = $deliveryId;

        return $this;
    }

    public function getDelivery(): Delivery|null
    {
        return $this->delivery;
    }

    public function setDelivery(Delivery|null $delivery): static
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
