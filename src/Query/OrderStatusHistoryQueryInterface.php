<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface OrderStatusHistoryQueryInterface
{
    public function findByOrderId(int $orderId): array;

    public function create(int $orderId, int $orderStatusId, int|null $memberId = null, string|null $note = null): int;
}
