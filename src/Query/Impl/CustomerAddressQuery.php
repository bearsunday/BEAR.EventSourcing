<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\CustomerAddressQueryInterface;
use DateTimeImmutable;

use function array_keys;
use function array_map;
use function array_merge;
use function implode;

class CustomerAddressQuery implements CustomerAddressQueryInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo,
    ) {
    }

    public function findById(int $id): array|null
    {
        $result = $this->pdo->fetchOne(
            'SELECT ca.*, p.name as pref_name
             FROM customer_address ca
             LEFT JOIN mtb_pref p ON ca.pref_id = p.id
             WHERE ca.id = :id',
            ['id' => $id],
        );

        return $result ?: null;
    }

    public function findByCustomerId(int $customerId): array
    {
        return $this->pdo->fetchAll(
            'SELECT ca.*, p.name as pref_name
             FROM customer_address ca
             LEFT JOIN mtb_pref p ON ca.pref_id = p.id
             WHERE ca.customer_id = :customer_id
             ORDER BY ca.is_default DESC, ca.id ASC',
            ['customer_id' => $customerId],
        );
    }

    public function findDefaultByCustomerId(int $customerId): array|null
    {
        $result = $this->pdo->fetchOne(
            'SELECT ca.*, p.name as pref_name
             FROM customer_address ca
             LEFT JOIN mtb_pref p ON ca.pref_id = p.id
             WHERE ca.customer_id = :customer_id AND ca.is_default = 1',
            ['customer_id' => $customerId],
        );

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'INSERT INTO customer_address (customer_id, name01, name02, kana01, kana02, company_name,
                postal_code, pref_id, addr01, addr02, phone_number, is_default, create_date, update_date)
             VALUES (:customer_id, :name01, :name02, :kana01, :kana02, :company_name,
                :postal_code, :pref_id, :addr01, :addr02, :phone_number, :is_default, :create_date, :update_date)',
            array_merge($data, [
                'is_default' => $data['is_default'] ?? 0,
                'create_date' => $now,
                'update_date' => $now,
            ]),
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(static fn ($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE customer_address SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function setDefault(int $customerId, int $addressId): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'UPDATE customer_address SET is_default = 0, update_date = :update_date WHERE customer_id = :customer_id',
            ['customer_id' => $customerId, 'update_date' => $now],
        );
        $this->pdo->perform(
            'UPDATE customer_address SET is_default = 1, update_date = :update_date WHERE id = :id',
            ['id' => $addressId, 'update_date' => $now],
        );
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM customer_address WHERE id = :id', ['id' => $id]);
    }
}
