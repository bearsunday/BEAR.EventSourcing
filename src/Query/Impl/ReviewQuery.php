<?php

declare(strict_types=1);

namespace BearEccube\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BearEccube\Entity\Master\ReviewStatus;
use BearEccube\Query\ReviewQueryInterface;
use DateTimeImmutable;

class ReviewQuery implements ReviewQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}

    public function findByProductId(int $productId, int $limit = 10, int $offset = 0): array
    {
        return $this->pdo->fetchAll(
            'SELECT * FROM review WHERE product_id = :product_id AND visible = 1 ORDER BY create_date DESC LIMIT :limit OFFSET :offset',
            ['product_id' => $productId, 'limit' => $limit, 'offset' => $offset]
        );
    }

    public function countByProductId(int $productId): int
    {
        return (int)$this->pdo->fetchValue(
            'SELECT COUNT(*) FROM review WHERE product_id = :product_id AND visible = 1',
            ['product_id' => $productId]
        );
    }

    public function getStats(int $productId): array
    {
        $result = $this->pdo->fetchOne(
            'SELECT COUNT(*) as count, AVG(rating) as average,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star5,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star4,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star3,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star2,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star1
             FROM review WHERE product_id = :product_id AND visible = 1',
            ['product_id' => $productId]
        );

        return [
            'count' => (int)($result['count'] ?? 0),
            'average' => round((float)($result['average'] ?? 0), 1),
            'distribution' => [
                5 => (int)($result['star5'] ?? 0),
                4 => (int)($result['star4'] ?? 0),
                3 => (int)($result['star3'] ?? 0),
                2 => (int)($result['star2'] ?? 0),
                1 => (int)($result['star1'] ?? 0),
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->pdo->fetchOne('SELECT * FROM review WHERE id = :id', ['id' => $id]) ?: null;
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['create_date'] = $now;
        $data['update_date'] = $now;
        $cols = implode(', ', array_keys($data));
        $ph = ':' . implode(', :', array_keys($data));
        $this->pdo->perform("INSERT INTO review ({$cols}) VALUES ({$ph})", $data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(fn($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE review SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM review WHERE id = :id', ['id' => $id]);
    }

    public function approve(int $id): void
    {
        $this->update($id, ['status_id' => ReviewStatus::APPROVED, 'visible' => true]);
    }

    public function reject(int $id): void
    {
        $this->update($id, ['status_id' => ReviewStatus::REJECTED, 'visible' => false]);
    }
}
