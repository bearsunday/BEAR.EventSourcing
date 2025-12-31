<?php

declare(strict_types=1);

namespace BearEccube\Entity;

use BearEccube\Entity\Master\OrderStatus;
use BearEccube\Entity\Master\Pref;
use DateTimeImmutable;

/**
 * Order entity (注文)
 */
class Order extends AbstractEntity
{
    protected ?int $id = null;
    protected ?string $preOrderId = null;
    protected ?int $customerId = null;
    protected ?Customer $customer = null;
    protected ?OrderStatus $orderStatus = null;
    protected ?Payment $payment = null;
    protected string $orderNo = '';
    protected ?string $message = null;
    protected string $name01 = '';
    protected string $name02 = '';
    protected ?string $kana01 = null;
    protected ?string $kana02 = null;
    protected ?string $companyName = null;
    protected ?string $email = null;
    protected ?string $phoneNumber = null;
    protected ?string $postalCode = null;
    protected ?Pref $pref = null;
    protected ?string $addr01 = null;
    protected ?string $addr02 = null;
    protected string $subtotal = '0';
    protected string $discount = '0';
    protected string $deliveryFeeTotal = '0';
    protected string $charge = '0';
    protected string $tax = '0';
    protected string $total = '0';
    protected string $paymentTotal = '0';
    protected ?DateTimeImmutable $paymentDate = null;
    protected ?DateTimeImmutable $orderDate = null;
    protected ?string $note = null;
    protected int $addPoint = 0;
    protected int $usePoint = 0;
    /** @var OrderItem[] */
    protected array $orderItems = [];
    /** @var Shipping[] */
    protected array $shippings = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getPreOrderId(): ?string
    {
        return $this->preOrderId;
    }

    public function setPreOrderId(?string $preOrderId): static
    {
        $this->preOrderId = $preOrderId;
        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): static
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    public function getOrderStatus(): ?OrderStatus
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(?OrderStatus $orderStatus): static
    {
        $this->orderStatus = $orderStatus;
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

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setOrderNo(string $orderNo): static
    {
        $this->orderNo = $orderNo;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
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

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getDiscount(): string
    {
        return $this->discount;
    }

    public function setDiscount(string $discount): static
    {
        $this->discount = $discount;
        return $this;
    }

    public function getDeliveryFeeTotal(): string
    {
        return $this->deliveryFeeTotal;
    }

    public function setDeliveryFeeTotal(string $deliveryFeeTotal): static
    {
        $this->deliveryFeeTotal = $deliveryFeeTotal;
        return $this;
    }

    public function getCharge(): string
    {
        return $this->charge;
    }

    public function setCharge(string $charge): static
    {
        $this->charge = $charge;
        return $this;
    }

    public function getTax(): string
    {
        return $this->tax;
    }

    public function setTax(string $tax): static
    {
        $this->tax = $tax;
        return $this;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function getPaymentTotal(): string
    {
        return $this->paymentTotal;
    }

    public function setPaymentTotal(string $paymentTotal): static
    {
        $this->paymentTotal = $paymentTotal;
        return $this;
    }

    public function getPaymentDate(): ?DateTimeImmutable
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(?DateTimeImmutable $paymentDate): static
    {
        $this->paymentDate = $paymentDate;
        return $this;
    }

    public function getOrderDate(): ?DateTimeImmutable
    {
        return $this->orderDate;
    }

    public function setOrderDate(?DateTimeImmutable $orderDate): static
    {
        $this->orderDate = $orderDate;
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

    public function getAddPoint(): int
    {
        return $this->addPoint;
    }

    public function setAddPoint(int $addPoint): static
    {
        $this->addPoint = $addPoint;
        return $this;
    }

    public function getUsePoint(): int
    {
        return $this->usePoint;
    }

    public function setUsePoint(int $usePoint): static
    {
        $this->usePoint = $usePoint;
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
     * @return Shipping[]
     */
    public function getShippings(): array
    {
        return $this->shippings;
    }

    /**
     * @param Shipping[] $shippings
     */
    public function setShippings(array $shippings): static
    {
        $this->shippings = $shippings;
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

    /**
     * Get product items only (excluding delivery fee, charge, discount, etc.)
     *
     * @return OrderItem[]
     */
    public function getProductOrderItems(): array
    {
        return array_filter(
            $this->orderItems,
            fn(OrderItem $item) => $item->isProduct()
        );
    }
}
