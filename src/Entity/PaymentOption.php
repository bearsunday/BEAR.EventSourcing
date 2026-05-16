<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Payment option entity (支払方法オプション)
 */
class PaymentOption extends AbstractEntity
{
    protected ?int $deliveryId = null;
    protected ?int $paymentId = null;
    protected ?Delivery $delivery = null;
    protected ?Payment $payment = null;

    public function getDeliveryId(): ?int
    {
        return $this->deliveryId;
    }

    public function setDeliveryId(?int $deliveryId): static
    {
        $this->deliveryId = $deliveryId;
        return $this;
    }

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }

    public function setPaymentId(?int $paymentId): static
    {
        $this->paymentId = $paymentId;
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

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;
        return $this;
    }
}
