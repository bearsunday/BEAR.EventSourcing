<?php

declare(strict_types=1);

namespace BearEccube\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BearEccube\Query\PaymentQueryInterface;

class PaymentQuery implements PaymentQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}
    public function findAll(): array { return $this->pdo->fetchAll('SELECT * FROM payment WHERE visible = 1 ORDER BY sort_no'); }
    public function findById(int $id): ?array { return $this->pdo->fetchOne('SELECT * FROM payment WHERE id = :id', ['id' => $id]) ?: null; }
    public function findByDeliveryId(int $deliveryId): array { return $this->pdo->fetchAll('SELECT p.* FROM payment p JOIN payment_option po ON p.id = po.payment_id WHERE po.delivery_id = :delivery_id AND p.visible = 1 ORDER BY p.sort_no', ['delivery_id' => $deliveryId]); }
}
