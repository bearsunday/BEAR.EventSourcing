<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface PointQueryInterface
{
    public function getBalance(int $customerId): int;

    public function getHistory(int $customerId, int $limit = 20, int $offset = 0): array;

    public function addPoints(int $customerId, int $point, int $actionType, string|null $reason = null, int|null $orderId = null): int;

    public function usePoints(int $customerId, int $point, int $orderId): bool;

    public function adjustPoints(int $customerId, int $point, string $reason): int;
}
