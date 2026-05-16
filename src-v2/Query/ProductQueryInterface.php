<?php

declare(strict_types=1);

namespace BearEccube\Query;

/**
 * Product Query Interface
 *
 * Outside-In: JsonSchema (var/schema/*.json) が契約の唯一の真実。
 */
interface ProductQueryInterface
{
    /** @return array{products: list<array<string, mixed>>, total: int, limit: int, offset: int} */
    public function findList(
        ?string $name = null,
        ?int $categoryId = null,
        ?int $statusId = null,
        int $limit = 20,
        int $offset = 0
    ): array;

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array;
}
