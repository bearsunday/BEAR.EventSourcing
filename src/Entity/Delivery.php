<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\SaleType;

/**
 * Delivery entity (配送方法)
 */
class Delivery extends AbstractEntity
{
    protected int|null $id = null;
    protected SaleType|null $saleType = null;
    protected string $name = '';
    protected string|null $serviceName = null;
    protected string|null $description = null;
    protected string|null $confirmUrl = null;
    protected int $sortNo = 0;
    protected bool $visible = true;
    /** @var DeliveryFee[] */
    protected array $deliveryFees = [];
    /** @var DeliveryTime[] */
    protected array $deliveryTimes = [];
    /** @var PaymentOption[] */
    protected array $paymentOptions = [];

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getSaleType(): SaleType|null
    {
        return $this->saleType;
    }

    public function setSaleType(SaleType|null $saleType): static
    {
        $this->saleType = $saleType;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getServiceName(): string|null
    {
        return $this->serviceName;
    }

    public function setServiceName(string|null $serviceName): static
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }

    public function setDescription(string|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getConfirmUrl(): string|null
    {
        return $this->confirmUrl;
    }

    public function setConfirmUrl(string|null $confirmUrl): static
    {
        $this->confirmUrl = $confirmUrl;

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

    /** @return DeliveryFee[] */
    public function getDeliveryFees(): array
    {
        return $this->deliveryFees;
    }

    /** @param DeliveryFee[] $deliveryFees */
    public function setDeliveryFees(array $deliveryFees): static
    {
        $this->deliveryFees = $deliveryFees;

        return $this;
    }

    /** @return DeliveryTime[] */
    public function getDeliveryTimes(): array
    {
        return $this->deliveryTimes;
    }

    /** @param DeliveryTime[] $deliveryTimes */
    public function setDeliveryTimes(array $deliveryTimes): static
    {
        $this->deliveryTimes = $deliveryTimes;

        return $this;
    }

    /** @return PaymentOption[] */
    public function getPaymentOptions(): array
    {
        return $this->paymentOptions;
    }

    /** @param PaymentOption[] $paymentOptions */
    public function setPaymentOptions(array $paymentOptions): static
    {
        $this->paymentOptions = $paymentOptions;

        return $this;
    }
}
