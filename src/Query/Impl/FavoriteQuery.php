<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\FavoriteQueryInterface;
use DateTimeImmutable;

class FavoriteQuery implements FavoriteQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}

    public function findByCustomerId(int $customerId, int $limit = 20, int $offset = 0): array
    {
        return $this->pdo->fetchAll(
            'SELECT f.*, p.name AS product_name, p.description_list,
                    (SELECT file_name FROM product_image pi WHERE pi.product_id = p.id ORDER BY sort_no LIMIT 1) AS main_image
             FROM customer_favorite_product f
             JOIN product p ON f.product_id = p.id
             WHERE f.customer_id = :customer_id
             ORDER BY f.create_date DESC
             LIMIT :limit OFFSET :offset',
            ['customer_id' => $customerId, 'limit' => $limit, 'offset' => $offset]
        );
    }

    public function countByCustomerId(int $customerId): int
    {
        return (int)$this->pdo->fetchValue(
            'SELECT COUNT(*) FROM customer_favorite_product WHERE customer_id = :customer_id',
            ['customer_id' => $customerId]
        );
    }

    public function exists(int $customerId, int $productId): bool
    {
        $result = $this->pdo->fetchValue(
            'SELECT 1 FROM customer_favorite_product WHERE customer_id = :customer_id AND product_id = :product_id',
            ['customer_id' => $customerId, 'product_id' => $productId]
        );
        return $result !== false;
    }

    public function add(int $customerId, int $productId): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'INSERT INTO customer_favorite_product (customer_id, product_id, create_date, update_date) VALUES (:customer_id, :product_id, :create_date, :update_date)',
            ['customer_id' => $customerId, 'product_id' => $productId, 'create_date' => $now, 'update_date' => $now]
        );
        return (int)$this->pdo->lastInsertId();
    }

    public function remove(int $customerId, int $productId): void
    {
        $this->pdo->perform(
            'DELETE FROM customer_favorite_product WHERE customer_id = :customer_id AND product_id = :product_id',
            ['customer_id' => $customerId, 'product_id' => $productId]
        );
    }
}
