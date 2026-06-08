<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\Pref;
use DateTimeImmutable;

/**
 * Shipping entity (配送)
 */
class Shipping extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $orderId = null;
    protected Order|null $order = null;
    protected Delivery|null $delivery = null;
    protected string $name01 = '';
    protected string $name02 = '';
    protected string|null $kana01 = null;
    protected string|null $kana02 = null;
    protected string|null $companyName = null;
    protected string|null $phoneNumber = null;
    protected string|null $postalCode = null;
    protected Pref|null $pref = null;
    protected string|null $addr01 = null;
    protected string|null $addr02 = null;
    protected DateTimeImmutable|null $shippingDeliveryDate = null;
    protected string|null $shippingDeliveryTime = null;
    protected DateTimeImmutable|null $shippingDate = null;
    protected string|null $trackingNumber = null;
    protected string|null $note = null;
    protected int $sortNo = 0;
    protected bool $mailSendFlg = false;
    /** @var OrderItem[] */
    protected array $orderItems = [];

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getOrderId(): int|null
    {
        return $this->orderId;
    }

    public function setOrderId(int|null $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getOrder(): Order|null
    {
        return $this->order;
    }

    public function setOrder(Order|null $order): static
    {
        $this->order = $order;

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

    public function getName01(): string
    {
        return $this->name01;
    }

    public function setName01(string $name01): static
    {
        $this->name01 = $name01;

        return $this;
    }

    public function getName02(): string
    {
        return $this->name02;
    }

    public function setName02(string $name02): static
    {
        $this->name02 = $name02;

        return $this;
    }

    public function getKana01(): string|null
    {
        return $this->kana01;
    }

    public function setKana01(string|null $kana01): static
    {
        $this->kana01 = $kana01;

        return $this;
    }

    public function getKana02(): string|null
    {
        return $this->kana02;
    }

    public function setKana02(string|null $kana02): static
    {
        $this->kana02 = $kana02;

        return $this;
    }

    public function getCompanyName(): string|null
    {
        return $this->companyName;
    }

    public function setCompanyName(string|null $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getPhoneNumber(): string|null
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string|null $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getPostalCode(): string|null
    {
        return $this->postalCode;
    }

    public function setPostalCode(string|null $postalCode): static
    {
        $this->postalCode = $postalCode;

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

    public function getAddr01(): string|null
    {
        return $this->addr01;
    }

    public function setAddr01(string|null $addr01): static
    {
        $this->addr01 = $addr01;

        return $this;
    }

    public function getAddr02(): string|null
    {
        return $this->addr02;
    }

    public function setAddr02(string|null $addr02): static
    {
        $this->addr02 = $addr02;

        return $this;
    }

    public function getShippingDeliveryDate(): DateTimeImmutable|null
    {
        return $this->shippingDeliveryDate;
    }

    public function setShippingDeliveryDate(DateTimeImmutable|null $shippingDeliveryDate): static
    {
        $this->shippingDeliveryDate = $shippingDeliveryDate;

        return $this;
    }

    public function getShippingDeliveryTime(): string|null
    {
        return $this->shippingDeliveryTime;
    }

    public function setShippingDeliveryTime(string|null $shippingDeliveryTime): static
    {
        $this->shippingDeliveryTime = $shippingDeliveryTime;

        return $this;
    }

    public function getShippingDate(): DateTimeImmutable|null
    {
        return $this->shippingDate;
    }

    public function setShippingDate(DateTimeImmutable|null $shippingDate): static
    {
        $this->shippingDate = $shippingDate;

        return $this;
    }

    public function getTrackingNumber(): string|null
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(string|null $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getNote(): string|null
    {
        return $this->note;
    }

    public function setNote(string|null $note): static
    {
        $this->note = $note;

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

    public function isMailSendFlg(): bool
    {
        return $this->mailSendFlg;
    }

    public function setMailSendFlg(bool $mailSendFlg): static
    {
        $this->mailSendFlg = $mailSendFlg;

        return $this;
    }

    /** @return OrderItem[] */
    public function getOrderItems(): array
    {
        return $this->orderItems;
    }

    /** @param OrderItem[] $orderItems */
    public function setOrderItems(array $orderItems): static
    {
        $this->orderItems = $orderItems;

        return $this;
    }

    /**
     * Get full name
     */
    public function getName(): string
    {
        return $this->name01 . ' ' . $this->name02;
    }

    /**
     * Get full address
     */
    public function getAddress(): string
    {
        $prefName = $this->pref?->getName() ?? '';

        return $prefName . ($this->addr01 ?? '') . ($this->addr02 ?? '');
    }
}
