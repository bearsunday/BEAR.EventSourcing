<?php

declare(strict_types=1);

namespace BearEccube\Query;

interface CategoryQueryInterface
{
    /** @return array{categories: list<array<string, mixed>>, total: int, limit: int, offset: int} */
    public function findList(?string $name = null, int $limit = 20, int $offset = 0): array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;
}
