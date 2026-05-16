<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface ReviewQueryInterface
{
    public function findByProductId(int $productId, int $limit = 10, int $offset = 0): array;
    public function countByProductId(int $productId): int;
    public function getStats(int $productId): array;
    public function findById(int $id): ?array;
    public function create(array $data): int;
    public function update(int $id, array $data): void;
    public function delete(int $id): void;
    public function approve(int $id): void;
    public function reject(int $id): void;
}
