<?php

declare(strict_types=1);

namespace BearEccube\Query;

interface CouponQueryInterface
{
    public function findByCode(string $code): ?array;
    public function findById(int $id): ?array;
    public function findAll(int $limit = 20, int $offset = 0): array;
    public function count(): int;
    public function create(array $data): int;
    public function update(int $id, array $data): void;
    public function delete(int $id): void;
    public function incrementUsage(int $id): void;
    public function recordUsage(int $couponId, int $orderId, ?int $customerId, string $discount): void;
}
