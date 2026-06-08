<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\ShippingQueryInterface;
use DateTimeImmutable;

use function array_keys;
use function array_map;
use function implode;

class ShippingQuery implements ShippingQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo)
    {
    }

    public function findByOrderId(int $orderId): array
    {
        return $this->pdo->fetchAll('SELECT * FROM shipping WHERE order_id = :order_id', ['order_id' => $orderId]);
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['create_date'] = $now;
        $data['update_date'] = $now;
        $cols = implode(', ', array_keys($data));
        $ph = ':' . implode(', :', array_keys($data));
        $this->pdo->perform("INSERT INTO shipping ({$cols}) VALUES ({$ph})", $data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(static fn ($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE shipping SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }
}
