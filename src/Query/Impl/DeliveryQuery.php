<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\DeliveryQueryInterface;

class DeliveryQuery implements DeliveryQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo)
    {
    }

    public function findAll(): array
    {
        return $this->pdo->fetchAll('SELECT * FROM delivery WHERE visible = 1 ORDER BY sort_no');
    }

    public function findById(int $id): array|null
    {
        return $this->pdo->fetchOne('SELECT * FROM delivery WHERE id = :id', ['id' => $id]) ?: null;
    }

    public function getDeliveryFee(int $deliveryId, int $prefId): string
    {
        $result = $this->pdo->fetchOne('SELECT fee FROM delivery_fee WHERE delivery_id = :delivery_id AND pref_id = :pref_id', ['delivery_id' => $deliveryId, 'pref_id' => $prefId]);

        return $result['fee'] ?? '0';
    }
}
