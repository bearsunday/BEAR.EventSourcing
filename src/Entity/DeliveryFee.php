<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\Pref;

/**
 * Delivery fee entity (配送料金)
 */
class DeliveryFee extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $deliveryId = null;
    protected ?Delivery $delivery = null;
    protected ?Pref $pref = null;
    protected string $fee = '0';

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

    public function getPref(): ?Pref
    {
        return $this->pref;
    }

    public function setPref(?Pref $pref): static
    {
        $this->pref = $pref;
        return $this;
    }

    public function getFee(): string
    {
        return $this->fee;
    }

    public function setFee(string $fee): static
    {
        $this->fee = $fee;
        return $this;
    }
}
