<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\NewsQueryInterface;
use DateTimeImmutable;

class NewsQuery implements NewsQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}

    public function findAll(int $limit = 10, int $offset = 0): array
    {
        return $this->pdo->fetchAll(
            'SELECT * FROM news WHERE visible = 1 AND publish_date <= :now ORDER BY publish_date DESC LIMIT :limit OFFSET :offset',
            ['now' => (new DateTimeImmutable())->format('Y-m-d'), 'limit' => $limit, 'offset' => $offset]
        );
    }

    public function count(): int
    {
        return (int)$this->pdo->fetchValue(
            'SELECT COUNT(*) FROM news WHERE visible = 1 AND publish_date <= :now',
            ['now' => (new DateTimeImmutable())->format('Y-m-d')]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->pdo->fetchOne('SELECT * FROM news WHERE id = :id', ['id' => $id]) ?: null;
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['create_date'] = $now;
        $data['update_date'] = $now;
        $cols = implode(', ', array_keys($data));
        $ph = ':' . implode(', :', array_keys($data));
        $this->pdo->perform("INSERT INTO news ({$cols}) VALUES ({$ph})", $data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(fn($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE news SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM news WHERE id = :id', ['id' => $id]);
    }
}
