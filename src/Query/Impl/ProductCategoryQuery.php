<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\ProductCategoryQueryInterface;

class ProductCategoryQuery implements ProductCategoryQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo)
    {
    }

    public function findByProductId(int $productId): array
    {
        return $this->pdo->fetchAll('SELECT c.* FROM category c JOIN product_category pc ON c.id = pc.category_id WHERE pc.product_id = :product_id', ['product_id' => $productId]);
    }

    public function addCategory(int $productId, int $categoryId): void
    {
        $this->pdo->perform('INSERT IGNORE INTO product_category (product_id, category_id) VALUES (:product_id, :category_id)', ['product_id' => $productId, 'category_id' => $categoryId]);
    }

    public function removeCategory(int $productId, int $categoryId): void
    {
        $this->pdo->perform('DELETE FROM product_category WHERE product_id = :product_id AND category_id = :category_id', ['product_id' => $productId, 'category_id' => $categoryId]);
    }
}
