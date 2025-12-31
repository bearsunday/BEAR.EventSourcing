<?php

declare(strict_types=1);

namespace BearEccube\Query;

interface MailTemplateQueryInterface
{
    public function findById(int $id): ?array;
    public function findByName(string $name): ?array;
    public function findAll(): array;
    public function create(array $data): int;
    public function update(int $id, array $data): void;
    public function delete(int $id): void;
}
