<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface CustomerAddressQueryInterface
{
    public function findById(int $id): array|null;

    public function findByCustomerId(int $customerId): array;

    public function findDefaultByCustomerId(int $customerId): array|null;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function setDefault(int $customerId, int $addressId): void;

    public function delete(int $id): void;
}
