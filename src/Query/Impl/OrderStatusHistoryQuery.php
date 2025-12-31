<?php

declare(strict_types=1);

namespace BearEccube\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BearEccube\Query\OrderStatusHistoryQueryInterface;
use DateTimeImmutable;

class OrderStatusHistoryQuery implements OrderStatusHistoryQueryInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo
    ) {}

    public function findByOrderId(int $orderId): array
    {
        return $this->pdo->fetchAll(
            'SELECT osh.*, os.name as status_name, m.name as member_name
             FROM order_status_history osh
             LEFT JOIN mtb_order_status os ON osh.order_status_id = os.id
             LEFT JOIN member m ON osh.member_id = m.id
             WHERE osh.order_id = :order_id
             ORDER BY osh.create_date ASC',
            ['order_id' => $orderId]
        );
    }

    public function create(int $orderId, int $orderStatusId, ?int $memberId = null, ?string $note = null): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'INSERT INTO order_status_history (order_id, order_status_id, member_id, note, create_date)
             VALUES (:order_id, :order_status_id, :member_id, :note, :create_date)',
            [
                'order_id' => $orderId,
                'order_status_id' => $orderStatusId,
                'member_id' => $memberId,
                'note' => $note,
                'create_date' => $now,
            ]
        );
        return (int)$this->pdo->lastInsertId();
    }
}
