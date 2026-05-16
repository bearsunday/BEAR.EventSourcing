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
    protected ?int $id = null;
    protected ?int $orderId = null;
    protected ?Order $order = null;
    protected ?Delivery $delivery = null;
    protected string $name01 = '';
    protected string $name02 = '';
    protected ?string $kana01 = null;
    protected ?string $kana02 = null;
    protected ?string $companyName = null;
    protected ?string $phoneNumber = null;
    protected ?string $postalCode = null;
    protected ?Pref $pref = null;
    protected ?string $addr01 = null;
    protected ?string $addr02 = null;
    protected ?DateTimeImmutable $shippingDeliveryDate = null;
    protected ?string $shippingDeliveryTime = null;
    protected ?DateTimeImmutable $shippingDate = null;
    protected ?string $trackingNumber = null;
    protected ?string $note = null;
    protected int $sortNo = 0;
    protected bool $mailSendFlg = false;
    /** @var OrderItem[] */
    protected array $orderItems = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(?int $orderId): static
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
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

    public function getKana01(): ?string
    {
        return $this->kana01;
    }

    public function setKana01(?string $kana01): static
    {
        $this->kana01 = $kana01;
        return $this;
    }

    public function getKana02(): ?string
    {
        return $this->kana02;
    }

    public function setKana02(?string $kana02): static
    {
        $this->kana02 = $kana02;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
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

    public function getAddr01(): ?string
    {
        return $this->addr01;
    }

    public function setAddr01(?string $addr01): static
    {
        $this->addr01 = $addr01;
        return $this;
    }

    public function getAddr02(): ?string
    {
        return $this->addr02;
    }

    public function setAddr02(?string $addr02): static
    {
        $this->addr02 = $addr02;
        return $this;
    }

    public function getShippingDeliveryDate(): ?DateTimeImmutable
    {
        return $this->shippingDeliveryDate;
    }

    public function setShippingDeliveryDate(?DateTimeImmutable $shippingDeliveryDate): static
    {
        $this->shippingDeliveryDate = $shippingDeliveryDate;
        return $this;
    }

    public function getShippingDeliveryTime(): ?string
    {
        return $this->shippingDeliveryTime;
    }

    public function setShippingDeliveryTime(?string $shippingDeliveryTime): static
    {
        $this->shippingDeliveryTime = $shippingDeliveryTime;
        return $this;
    }

    public function getShippingDate(): ?DateTimeImmutable
    {
        return $this->shippingDate;
    }

    public function setShippingDate(?DateTimeImmutable $shippingDate): static
    {
        $this->shippingDate = $shippingDate;
        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
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

    /**
     * @return OrderItem[]
     */
    public function getOrderItems(): array
    {
        return $this->orderItems;
    }

    /**
     * @param OrderItem[] $orderItems
     */
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
