<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface PluginQueryInterface
{
    public function findById(int $id): ?array;

    public function findByCode(string $code): ?array;

    public function findAll(bool $enabledOnly = false): array;

    public function install(array $data): int;

    public function enable(int $id): void;

    public function disable(int $id): void;

    public function uninstall(int $id): void;

    public function update(int $id, array $data): void;
}
