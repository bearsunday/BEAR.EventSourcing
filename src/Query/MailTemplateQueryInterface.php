<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface MailTemplateQueryInterface
{
    public function findById(int $id): array|null;

    public function findByName(string $name): array|null;

    public function findAll(): array;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;
}
