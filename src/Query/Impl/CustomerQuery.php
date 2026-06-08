<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\CustomerQueryInterface;
use DateTimeImmutable;

use function array_keys;
use function array_map;
use function implode;

class CustomerQuery implements CustomerQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo)
    {
    }

    public function findAll(string|null $email = null, string|null $name = null, int|null $status = null, int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT c.*, cs.name AS status_name FROM customer c LEFT JOIN mtb_customer_status cs ON c.customer_status_id = cs.id WHERE 1=1';
        $params = [];
        if ($email) {
            $sql .= ' AND c.email LIKE :email';
            $params['email'] = "%{$email}%";
        }

        if ($name) {
            $sql .= ' AND (c.name01 LIKE :name OR c.name02 LIKE :name)';
            $params['name'] = "%{$name}%";
        }

        if ($status) {
            $sql .= ' AND c.customer_status_id = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY c.update_date DESC LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->pdo->fetchAll($sql, $params);
    }

    public function count(string|null $email = null, string|null $name = null, int|null $status = null): int
    {
        $sql = 'SELECT COUNT(*) FROM customer c WHERE 1=1';
        $params = [];
        if ($email) {
            $sql .= ' AND c.email LIKE :email';
            $params['email'] = "%{$email}%";
        }

        if ($name) {
            $sql .= ' AND (c.name01 LIKE :name OR c.name02 LIKE :name)';
            $params['name'] = "%{$name}%";
        }

        if ($status) {
            $sql .= ' AND c.customer_status_id = :status';
            $params['status'] = $status;
        }

        return (int) $this->pdo->fetchValue($sql, $params);
    }

    public function findById(int $id): array|null
    {
        return $this->pdo->fetchOne('SELECT * FROM customer WHERE id = :id', ['id' => $id]) ?: null;
    }

    public function findByEmail(string $email): array|null
    {
        return $this->pdo->fetchOne('SELECT * FROM customer WHERE email = :email', ['email' => $email]) ?: null;
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['create_date'] = $now;
        $data['update_date'] = $now;
        $cols = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $this->pdo->perform("INSERT INTO customer ({$cols}) VALUES ({$placeholders})", $data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(static fn ($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE customer SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM customer WHERE id = :id', ['id' => $id]);
    }

    public function updatePoint(int $id, int $point): void
    {
        $this->pdo->perform('UPDATE customer SET point = point + :point WHERE id = :id', ['id' => $id, 'point' => $point]);
    }
}
