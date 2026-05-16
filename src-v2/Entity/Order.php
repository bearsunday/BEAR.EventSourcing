<?php

declare(strict_types=1);

namespace BearEccube\Entity;

final readonly class Order
{
    public function __construct(
        public int $id,
        public string $orderNo,
        public ?string $preOrderId,
        public ?string $message,
        public string $name01,
        public string $name02,
        public ?string $kana01,
        public ?string $kana02,
        public ?string $companyName,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $postalCode,
        public ?string $addr01,
        public ?string $addr02,
        public int $subtotal,
        public int $discount,
        public int $deliveryFeeTotal,
        public int $charge,
        public int $tax,
        public int $total,
        public int $paymentTotal,
        public ?string $currencyCode,
        public string $createDate,
        public string $updateDate,
        public ?string $orderDate,
        public ?string $paymentDate,
        public int $customerId,
        public int $statusId,
    ) {
    }

    public function isPaid(): bool
    {
        return $this->paymentDate !== null;
    }

    public function customerName(): string
    {
        return $this->name01 . ' ' . $this->name02;
    }
}
