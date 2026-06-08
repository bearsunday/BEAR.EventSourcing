<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface TaxRuleQueryInterface
{
    public function findApplicable(int|null $productClassId = null, int|null $prefId = null): array|null;

    public function findAll(): array;

    public function findById(int $id): array|null;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;
}
