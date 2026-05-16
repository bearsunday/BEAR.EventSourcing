<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\ProductClassQueryInterface;
use DateTimeImmutable;

class ProductClassQuery implements ProductClassQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}
    public function findByProductId(int $productId): array { return $this->pdo->fetchAll('SELECT * FROM product_class WHERE product_id = :product_id AND visible = 1', ['product_id' => $productId]); }
    public function findById(int $id): ?array { return $this->pdo->fetchOne('SELECT * FROM product_class WHERE id = :id', ['id' => $id]) ?: null; }
    public function create(array $data): int { $now = (new DateTimeImmutable())->format('Y-m-d H:i:s'); $data['create_date'] = $now; $data['update_date'] = $now; $cols = implode(', ', array_keys($data)); $ph = ':' . implode(', :', array_keys($data)); $this->pdo->perform("INSERT INTO product_class ({$cols}) VALUES ({$ph})", $data); return (int)$this->pdo->lastInsertId(); }
    public function update(int $id, array $data): void { $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s'); $sets = array_map(fn($k) => "{$k} = :{$k}", array_keys($data)); $data['id'] = $id; $this->pdo->perform('UPDATE product_class SET ' . implode(', ', $sets) . ' WHERE id = :id', $data); }
    public function delete(int $id): void { $this->pdo->perform('DELETE FROM product_class WHERE id = :id', ['id' => $id]); }
    public function updateStock(int $id, int $quantity): void { $this->pdo->perform('UPDATE product_class SET stock = stock + :quantity WHERE id = :id AND stock_unlimited = 0', ['id' => $id, 'quantity' => $quantity]); }
}
