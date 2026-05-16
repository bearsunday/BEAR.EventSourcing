<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\PointQueryInterface;
use DateTimeImmutable;

class PointQuery implements PointQueryInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo
    ) {}

    public function getBalance(int $customerId): int
    {
        $result = $this->pdo->fetchValue(
            'SELECT COALESCE(SUM(point), 0) FROM point_history WHERE customer_id = :customer_id',
            ['customer_id' => $customerId]
        );
        return (int)$result;
    }

    public function getHistory(int $customerId, int $limit = 20, int $offset = 0): array
    {
        return $this->pdo->fetchAll(
            'SELECT * FROM point_history
             WHERE customer_id = :customer_id
             ORDER BY create_date DESC
             LIMIT :limit OFFSET :offset',
            ['customer_id' => $customerId, 'limit' => $limit, 'offset' => $offset]
        );
    }

    public function addPoints(int $customerId, int $point, int $actionType, ?string $reason = null, ?int $orderId = null): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'INSERT INTO point_history (customer_id, order_id, point, action_type, reason, create_date)
             VALUES (:customer_id, :order_id, :point, :action_type, :reason, :create_date)',
            [
                'customer_id' => $customerId,
                'order_id' => $orderId,
                'point' => $point,
                'action_type' => $actionType,
                'reason' => $reason,
                'create_date' => $now,
            ]
        );
        $id = (int)$this->pdo->lastInsertId();

        // Update customer point balance
        $this->pdo->perform(
            'UPDATE customer SET point = point + :point, update_date = :update_date WHERE id = :id',
            ['point' => $point, 'update_date' => $now, 'id' => $customerId]
        );

        return $id;
    }

    public function usePoints(int $customerId, int $point, int $orderId): bool
    {
        $balance = $this->getBalance($customerId);
        if ($balance < $point) {
            return false;
        }

        $this->addPoints($customerId, -$point, 2, '注文でのポイント使用', $orderId);
        return true;
    }

    public function adjustPoints(int $customerId, int $point, string $reason): int
    {
        return $this->addPoints($customerId, $point, 3, $reason);
    }
}
