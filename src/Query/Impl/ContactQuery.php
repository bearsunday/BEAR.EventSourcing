<?php

declare(strict_types=1);

namespace BearEccube\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BearEccube\Query\ContactQueryInterface;
use DateTimeImmutable;

class ContactQuery implements ContactQueryInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo
    ) {}

    public function findById(int $id): ?array
    {
        $result = $this->pdo->fetchOne(
            'SELECT c.*, p.name as pref_name
             FROM contact c
             LEFT JOIN mtb_pref p ON c.pref_id = p.id
             WHERE c.id = :id',
            ['id' => $id]
        );
        return $result ?: null;
    }

    public function findByFilters(array $filters, int $limit = 20, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = ['limit' => $limit, 'offset' => $offset];

        if (isset($filters['status'])) {
            $where[] = 'c.status = :status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['email'])) {
            $where[] = 'c.email LIKE :email';
            $params['email'] = '%' . $filters['email'] . '%';
        }
        if (isset($filters['customer_id'])) {
            $where[] = 'c.customer_id = :customer_id';
            $params['customer_id'] = $filters['customer_id'];
        }

        return $this->pdo->fetchAll(
            'SELECT c.*, p.name as pref_name
             FROM contact c
             LEFT JOIN mtb_pref p ON c.pref_id = p.id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY c.create_date DESC
             LIMIT :limit OFFSET :offset',
            $params
        );
    }

    public function countByFilters(array $filters): int
    {
        $where = ['1=1'];
        $params = [];

        if (isset($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['email'])) {
            $where[] = 'email LIKE :email';
            $params['email'] = '%' . $filters['email'] . '%';
        }
        if (isset($filters['customer_id'])) {
            $where[] = 'customer_id = :customer_id';
            $params['customer_id'] = $filters['customer_id'];
        }

        return (int)$this->pdo->fetchValue(
            'SELECT COUNT(*) FROM contact WHERE ' . implode(' AND ', $where),
            $params
        );
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'INSERT INTO contact (customer_id, name01, name02, kana01, kana02, email, phone_number,
                postal_code, pref_id, addr01, addr02, subject, message, status, create_date, update_date)
             VALUES (:customer_id, :name01, :name02, :kana01, :kana02, :email, :phone_number,
                :postal_code, :pref_id, :addr01, :addr02, :subject, :message, :status, :create_date, :update_date)',
            array_merge($data, [
                'status' => $data['status'] ?? 1,
                'create_date' => $now,
                'update_date' => $now,
            ])
        );
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(fn($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE contact SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM contact WHERE id = :id', ['id' => $id]);
    }
}
