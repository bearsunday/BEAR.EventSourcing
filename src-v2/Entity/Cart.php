<?php

declare(strict_types=1);

namespace BearEccube\Entity;

final readonly class Cart
{
    public function __construct(
        public int $id,
        public ?string $cartKey,
        public ?string $preOrderId,
        public int $totalPrice,
        public int $deliveryFeeTotal,
        public ?int $sortNo,
        public string $createDate,
        public string $updateDate,
        public ?int $customerId,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->totalPrice === 0;
    }
}
