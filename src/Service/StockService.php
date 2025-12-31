<?php

declare(strict_types=1);

namespace BearEccube\Service;

use BearEccube\Query\ProductClassQueryInterface;

/**
 * Stock management service
 */
class StockService implements StockServiceInterface
{
    public function __construct(
        private readonly ProductClassQueryInterface $productClassQuery
    ) {
    }

    /**
     * @inheritDoc
     */
    public function checkStock(int $productClassId, int $quantity): bool
    {
        $productClass = $this->productClassQuery->findById($productClassId);

        if ($productClass === null) {
            return false;
        }

        if ($productClass['stock_unlimited']) {
            return true;
        }

        return $productClass['stock'] >= $quantity;
    }

    /**
     * @inheritDoc
     */
    public function getStock(int $productClassId): ?int
    {
        $productClass = $this->productClassQuery->findById($productClassId);

        if ($productClass === null) {
            return null;
        }

        if ($productClass['stock_unlimited']) {
            return null; // null indicates unlimited
        }

        return (int)$productClass['stock'];
    }

    /**
     * @inheritDoc
     */
    public function reduceStock(int $productClassId, int $quantity): bool
    {
        if (!$this->checkStock($productClassId, $quantity)) {
            return false;
        }

        $this->productClassQuery->updateStock($productClassId, -$quantity);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function addStock(int $productClassId, int $quantity): void
    {
        $this->productClassQuery->updateStock($productClassId, $quantity);
    }

    /**
     * @inheritDoc
     */
    public function setStock(int $productClassId, ?int $stock, bool $unlimited = false): void
    {
        $this->productClassQuery->update($productClassId, [
            'stock' => $stock,
            'stock_unlimited' => $unlimited,
        ]);
    }
}
