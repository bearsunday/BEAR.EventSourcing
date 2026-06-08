<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\MemberQueryInterface;
use DateTimeImmutable;

use function array_keys;
use function array_map;
use function implode;

class MemberQuery implements MemberQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo)
    {
    }

    public function findById(int $id): array|null
    {
        return $this->pdo->fetchOne('SELECT * FROM member WHERE id = :id', ['id' => $id]) ?: null;
    }

    public function findByLoginId(string $loginId): array|null
    {
        return $this->pdo->fetchOne('SELECT * FROM member WHERE login_id = :login_id', ['login_id' => $loginId]) ?: null;
    }

    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return $this->pdo->fetchAll('SELECT * FROM member ORDER BY sort_no LIMIT :limit OFFSET :offset', ['limit' => $limit, 'offset' => $offset]);
    }

    public function count(): int
    {
        return (int) $this->pdo->fetchValue('SELECT COUNT(*) FROM member');
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['create_date'] = $now;
        $data['update_date'] = $now;
        $cols = implode(', ', array_keys($data));
        $ph = ':' . implode(', :', array_keys($data));
        $this->pdo->perform("INSERT INTO member ({$cols}) VALUES ({$ph})", $data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(static fn ($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE member SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM member WHERE id = :id', ['id' => $id]);
    }
}
