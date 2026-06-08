<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\OrderStatus;
use BEAR\EventSourcing\Entity\Master\Pref;
use DateTimeImmutable;

use function array_filter;

/**
 * Order entity (注文)
 */
class Order extends AbstractEntity
{
    protected int|null $id = null;
    protected string|null $preOrderId = null;
    protected int|null $customerId = null;
    protected Customer|null $customer = null;
    protected OrderStatus|null $orderStatus = null;
    protected Payment|null $payment = null;
    protected string $orderNo = '';
    protected string|null $message = null;
    protected string $name01 = '';
    protected string $name02 = '';
    protected string|null $kana01 = null;
    protected string|null $kana02 = null;
    protected string|null $companyName = null;
    protected string|null $email = null;
    protected string|null $phoneNumber = null;
    protected string|null $postalCode = null;
    protected Pref|null $pref = null;
    protected string|null $addr01 = null;
    protected string|null $addr02 = null;
    protected string $subtotal = '0';
    protected string $discount = '0';
    protected string $deliveryFeeTotal = '0';
    protected string $charge = '0';
    protected string $tax = '0';
    protected string $total = '0';
    protected string $paymentTotal = '0';
    protected DateTimeImmutable|null $paymentDate = null;
    protected DateTimeImmutable|null $orderDate = null;
    protected string|null $note = null;
    protected int $addPoint = 0;
    protected int $usePoint = 0;
    /** @var OrderItem[] */
    protected array $orderItems = [];
    /** @var Shipping[] */
    protected array $shippings = [];

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPreOrderId(): string|null
    {
        return $this->preOrderId;
    }

    public function setPreOrderId(string|null $preOrderId): static
    {
        $this->preOrderId = $preOrderId;

        return $this;
    }

    public function getCustomerId(): int|null
    {
        return $this->customerId;
    }

    public function setCustomerId(int|null $customerId): static
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getCustomer(): Customer|null
    {
        return $this->customer;
    }

    public function setCustomer(Customer|null $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getOrderStatus(): OrderStatus|null
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(OrderStatus|null $orderStatus): static
    {
        $this->orderStatus = $orderStatus;

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

    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    public function setOrderNo(string $orderNo): static
    {
        $this->orderNo = $orderNo;

        return $this;
    }

    public function getMessage(): string|null
    {
        return $this->message;
    }

    public function setMessage(string|null $message): static
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

    public function getEmail(): string|null
    {
        return $this->email;
    }

    public function setEmail(string|null $email): static
    {
        $this->email = $email;

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

    public function getPaymentDate(): DateTimeImmutable|null
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(DateTimeImmutable|null $paymentDate): static
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getOrderDate(): DateTimeImmutable|null
    {
        return $this->orderDate;
    }

    public function setOrderDate(DateTimeImmutable|null $orderDate): static
    {
        $this->orderDate = $orderDate;

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

    /** @return Shipping[] */
    public function getShippings(): array
    {
        return $this->shippings;
    }

    /** @param Shipping[] $shippings */
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
            static fn (OrderItem $item) => $item->isProduct(),
        );
    }
}
