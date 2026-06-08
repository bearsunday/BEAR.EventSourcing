<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface FavoriteQueryInterface
{
    public function findByCustomerId(int $customerId, int $limit = 20, int $offset = 0): array;

    public function countByCustomerId(int $customerId): int;

    public function exists(int $customerId, int $productId): bool;

    public function add(int $customerId, int $productId): int;

    public function remove(int $customerId, int $productId): void;
}
