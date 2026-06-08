<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\Pref;

/**
 * Delivery fee entity (配送料金)
 */
class DeliveryFee extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $deliveryId = null;
    protected Delivery|null $delivery = null;
    protected Pref|null $pref = null;
    protected string $fee = '0';

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

    public function getPref(): Pref|null
    {
        return $this->pref;
    }

    public function setPref(Pref|null $pref): static
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
