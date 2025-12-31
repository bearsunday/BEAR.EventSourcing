<?php

declare(strict_types=1);

namespace BearEccube\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BearEccube\Entity\Master\OrderStatus;
use BearEccube\Query\OrderQueryInterface;
use DateTimeImmutable;

class OrderQuery implements OrderQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}

    public function findAll(?int $customerId = null, ?int $status = null, ?string $orderNo = null, ?string $dateFrom = null, ?string $dateTo = null, int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT o.*, os.name AS status_name FROM `order` o LEFT JOIN mtb_order_status os ON o.order_status_id = os.id WHERE 1=1';
        $params = [];
        if ($customerId) { $sql .= ' AND o.customer_id = :customer_id'; $params['customer_id'] = $customerId; }
        if ($status) { $sql .= ' AND o.order_status_id = :status'; $params['status'] = $status; }
        if ($orderNo) { $sql .= ' AND o.order_no LIKE :order_no'; $params['order_no'] = "%{$orderNo}%"; }
        if ($dateFrom) { $sql .= ' AND o.order_date >= :date_from'; $params['date_from'] = $dateFrom; }
        if ($dateTo) { $sql .= ' AND o.order_date <= :date_to'; $params['date_to'] = $dateTo; }
        $sql .= ' ORDER BY o.order_date DESC LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit; $params['offset'] = $offset;
        return $this->pdo->fetchAll($sql, $params);
    }

    public function count(?int $customerId = null, ?int $status = null, ?string $orderNo = null, ?string $dateFrom = null, ?string $dateTo = null): int
    {
        $sql = 'SELECT COUNT(*) FROM `order` o WHERE 1=1';
        $params = [];
        if ($customerId) { $sql .= ' AND o.customer_id = :customer_id'; $params['customer_id'] = $customerId; }
        if ($status) { $sql .= ' AND o.order_status_id = :status'; $params['status'] = $status; }
        if ($orderNo) { $sql .= ' AND o.order_no LIKE :order_no'; $params['order_no'] = "%{$orderNo}%"; }
        if ($dateFrom) { $sql .= ' AND o.order_date >= :date_from'; $params['date_from'] = $dateFrom; }
        if ($dateTo) { $sql .= ' AND o.order_date <= :date_to'; $params['date_to'] = $dateTo; }
        return (int)$this->pdo->fetchValue($sql, $params);
    }

    public function findById(int $id): ?array { return $this->pdo->fetchOne('SELECT * FROM `order` WHERE id = :id', ['id' => $id]) ?: null; }
    public function findByOrderNo(string $orderNo): ?array { return $this->pdo->fetchOne('SELECT * FROM `order` WHERE order_no = :order_no', ['order_no' => $orderNo]) ?: null; }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['create_date'] = $now; $data['update_date'] = $now;
        $cols = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $this->pdo->perform("INSERT INTO `order` ({$cols}) VALUES ({$placeholders})", $data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(fn($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE `order` SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function cancel(int $id): void { $this->update($id, ['order_status_id' => OrderStatus::CANCEL]); }
}
