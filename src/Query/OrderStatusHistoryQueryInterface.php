<?php

declare(strict_types=1);

namespace BearEccube\Query;

interface OrderStatusHistoryQueryInterface
{
    public function findByOrderId(int $orderId): array;

    public function create(int $orderId, int $orderStatusId, ?int $memberId = null, ?string $note = null): int;
}
