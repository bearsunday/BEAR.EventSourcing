<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface TaxRuleQueryInterface
{
    public function findApplicable(?int $productClassId = null, ?int $prefId = null): ?array;
    public function findAll(): array;
    public function findById(int $id): ?array;
    public function create(array $data): int;
    public function update(int $id, array $data): void;
    public function delete(int $id): void;
}
