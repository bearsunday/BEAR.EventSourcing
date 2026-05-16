<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use BearEccube\Query\CustomerQueryInterface;

class FakeCustomerQuery extends AbstractFakeQuery implements CustomerQueryInterface
{
    protected function fakeName(): string
    {
        return 'customer';
    }

    public function findList(
        ?string $name = null,
        ?string $email = null,
        ?int $statusId = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $customers = $this->loadItems();

        if ($name !== null) {
            $customers = array_values(array_filter(
                $customers,
                static fn($c) => str_contains($c['name01'] ?? '', $name)
                    || str_contains($c['name02'] ?? '', $name)
            ));
        }

        if ($email !== null) {
            $customers = array_values(array_filter(
                $customers,
                static fn($c) => str_contains($c['email'] ?? '', $email)
            ));
        }

        return [
            'customers' => $customers,
            'total' => count($customers),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->findItemById($id);
    }
}
