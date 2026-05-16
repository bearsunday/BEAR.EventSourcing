<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use BearEccube\Query\ProductQueryInterface;

class FakeProductQuery extends AbstractFakeQuery implements ProductQueryInterface
{
    protected function fakeName(): string
    {
        return 'product';
    }

    public function findList(
        ?string $name = null,
        ?int $categoryId = null,
        ?int $statusId = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $products = $this->loadItems();

        if ($name !== null) {
            $products = array_values(array_filter(
                $products,
                static fn($p) => str_contains($p['name'] ?? '', $name)
            ));
        }

        return [
            'products' => $products,
            'total' => count($products),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->findItemById($id);
    }
}
