<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\ProductImageQueryInterface;
use DateTimeImmutable;

use function array_keys;
use function implode;

class ProductImageQuery implements ProductImageQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo)
    {
    }

    public function findByProductId(int $productId): array
    {
        return $this->pdo->fetchAll('SELECT * FROM product_image WHERE product_id = :product_id ORDER BY sort_no', ['product_id' => $productId]);
    }

    public function create(array $data): int
    {
        $data['create_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $cols = implode(', ', array_keys($data));
        $ph = ':' . implode(', :', array_keys($data));
        $this->pdo->perform("INSERT INTO product_image ({$cols}) VALUES ({$ph})", $data);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM product_image WHERE id = :id', ['id' => $id]);
    }

    public function updateSortNo(int $id, int $sortNo): void
    {
        $this->pdo->perform('UPDATE product_image SET sort_no = :sort_no WHERE id = :id', ['id' => $id, 'sort_no' => $sortNo]);
    }
}
