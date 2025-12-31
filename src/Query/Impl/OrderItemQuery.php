<?php

declare(strict_types=1);

namespace BearEccube\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BearEccube\Query\OrderItemQueryInterface;
use DateTimeImmutable;

class OrderItemQuery implements OrderItemQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}
    public function findByOrderId(int $orderId): array { return $this->pdo->fetchAll('SELECT * FROM order_item WHERE order_id = :order_id', ['order_id' => $orderId]); }
    public function createItems(int $orderId, array $items): void { $now = (new DateTimeImmutable())->format('Y-m-d H:i:s'); foreach ($items as $item) { $item['order_id'] = $orderId; $item['create_date'] = $now; $item['update_date'] = $now; $cols = implode(', ', array_keys($item)); $ph = ':' . implode(', :', array_keys($item)); $this->pdo->perform("INSERT INTO order_item ({$cols}) VALUES ({$ph})", $item); } }
}
