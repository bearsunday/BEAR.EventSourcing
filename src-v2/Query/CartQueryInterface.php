<?php

declare(strict_types=1);

namespace BearEccube\Query;

interface CartQueryInterface
{
    /** @return array{carts: list<array<string, mixed>>, total: int, limit: int, offset: int} */
    public function findList(?int $customerId = null, int $limit = 20, int $offset = 0): array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;

    /** @return array<string, mixed>|null */
    public function findByCartKey(string $cartKey): ?array;
}
