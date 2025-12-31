<?php

declare(strict_types=1);

namespace BearEccube\Entity;

class OrderStatusHistory extends AbstractEntity
{
    public function __construct(
        public ?int $id = null,
        public int $orderId = 0,
        public int $orderStatusId = 0,
        public ?int $memberId = null,
        public ?string $note = null,
        public ?\DateTimeImmutable $createDate = null
    ) {}
}
