<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface NewsQueryInterface
{
    public function findAll(int $limit = 10, int $offset = 0): array;
    public function count(): int;
    public function findById(int $id): ?array;
    public function create(array $data): int;
    public function update(int $id, array $data): void;
    public function delete(int $id): void;
}
