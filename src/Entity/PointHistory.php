<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

class PointHistory extends AbstractEntity
{
    public function __construct(
        public ?int $id = null,
        public ?int $customerId = null,
        public ?int $orderId = null,
        public int $point = 0,
        public int $actionType = 1, // 1: add, 2: use, 3: adjust, 4: expire
        public ?string $reason = null,
        public ?\DateTimeImmutable $createDate = null
    ) {}
}
