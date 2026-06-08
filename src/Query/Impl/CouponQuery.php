<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\CouponQueryInterface;
use DateTimeImmutable;

use function array_keys;
use function array_map;
use function implode;

class CouponQuery implements CouponQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo)
    {
    }

    public function findByCode(string $code): array|null
    {
        $coupon = $this->pdo->fetchOne('SELECT * FROM coupon WHERE coupon_cd = :code AND visible = 1', ['code' => $code]);
        if (! $coupon) {
            return null;
        }

        $now = new DateTimeImmutable();
        $isAvailable = true;

        if ($coupon['available_from_date'] && new DateTimeImmutable($coupon['available_from_date']) > $now) {
            $isAvailable = false;
        }

        if ($coupon['available_to_date'] && new DateTimeImmutable($coupon['available_to_date']) < $now) {
            $isAvailable = false;
        }

        if ($coupon['coupon_use_time'] > 0 && $coupon['used_time'] >= $coupon['coupon_use_time']) {
            $isAvailable = false;
        }

        $coupon['is_available'] = $isAvailable;

        return $coupon;
    }

    public function findById(int $id): array|null
    {
        return $this->pdo->fetchOne('SELECT * FROM coupon WHERE id = :id', ['id' => $id]) ?: null;
    }

    public function findAll(int $limit = 20, int $offset = 0): array
    {
        return $this->pdo->fetchAll('SELECT * FROM coupon ORDER BY create_date DESC LIMIT :limit OFFSET :offset', ['limit' => $limit, 'offset' => $offset]);
    }

    public function count(): int
    {
        return (int) $this->pdo->fetchValue('SELECT COUNT(*) FROM coupon');
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['create_date'] = $now;
        $data['update_date'] = $now;
        $cols = implode(', ', array_keys($data));
        $ph = ':' . implode(', :', array_keys($data));
        $this->pdo->perform("INSERT INTO coupon ({$cols}) VALUES ({$ph})", $data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(static fn ($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE coupon SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM coupon WHERE id = :id', ['id' => $id]);
    }

    public function incrementUsage(int $id): void
    {
        $this->pdo->perform('UPDATE coupon SET used_time = used_time + 1 WHERE id = :id', ['id' => $id]);
    }

    public function recordUsage(int $couponId, int $orderId, int|null $customerId, string $discount): void
    {
        $coupon = $this->findById($couponId);
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->pdo->perform(
            'INSERT INTO coupon_order (coupon_id, order_id, customer_id, coupon_cd, coupon_name, discount_price, visible, create_date, update_date)
             VALUES (:coupon_id, :order_id, :customer_id, :coupon_cd, :coupon_name, :discount_price, 1, :create_date, :update_date)',
            [
                'coupon_id' => $couponId,
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'coupon_cd' => $coupon['coupon_cd'],
                'coupon_name' => $coupon['coupon_name'],
                'discount_price' => $discount,
                'create_date' => $now,
                'update_date' => $now,
            ],
        );

        $this->incrementUsage($couponId);
    }
}
