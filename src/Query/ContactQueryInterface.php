<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface ContactQueryInterface
{
    public function findById(int $id): array|null;

    public function findByFilters(array $filters, int $limit = 20, int $offset = 0): array;

    public function countByFilters(array $filters): int;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;
}
