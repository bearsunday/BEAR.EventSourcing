<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Payment option entity (支払方法オプション)
 */
class PaymentOption extends AbstractEntity
{
    protected int|null $deliveryId = null;
    protected int|null $paymentId = null;
    protected Delivery|null $delivery = null;
    protected Payment|null $payment = null;

    public function getDeliveryId(): int|null
    {
        return $this->deliveryId;
    }

    public function setDeliveryId(int|null $deliveryId): static
    {
        $this->deliveryId = $deliveryId;

        return $this;
    }

    public function getPaymentId(): int|null
    {
        return $this->paymentId;
    }

    public function setPaymentId(int|null $paymentId): static
    {
        $this->paymentId = $paymentId;

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

    public function getPayment(): Payment|null
    {
        return $this->payment;
    }

    public function setPayment(Payment|null $payment): static
    {
        $this->payment = $payment;

        return $this;
    }
}
