<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

class OrderStatusHistory extends AbstractEntity
{
    public function __construct(
        public int|null $id = null,
        public int $orderId = 0,
        public int $orderStatusId = 0,
        public int|null $memberId = null,
        public string|null $note = null,
        public DateTimeImmutable|null $createDate = null,
    ) {
    }
}
