<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

class PointHistory extends AbstractEntity
{
    public function __construct(
        public int|null $id = null,
        public int|null $customerId = null,
        public int|null $orderId = null,
        public int $point = 0,
        public int $actionType = 1, // 1: add, 2: use, 3: adjust, 4: expire
        public string|null $reason = null,
        public DateTimeImmutable|null $createDate = null,
    ) {
    }
}
