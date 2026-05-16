<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use BearEccube\Query\CategoryQueryInterface;

class FakeCategoryQuery extends AbstractFakeQuery implements CategoryQueryInterface
{
    protected function fakeName(): string
    {
        return 'category';
    }

    public function findList(?string $name = null, int $limit = 20, int $offset = 0): array
    {
        $categories = $this->loadItems();

        if ($name !== null) {
            $categories = array_values(array_filter(
                $categories,
                static fn($c) => str_contains($c['name'] ?? '', $name)
            ));
        }

        return [
            'categories' => $categories,
            'total' => count($categories),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->findItemById($id);
    }
}
