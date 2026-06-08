<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Service;

interface StockServiceInterface
{
    /**
     * Check if sufficient stock is available
     */
    public function checkStock(int $productClassId, int $quantity): bool;

    /**
     * Get current stock level (null = unlimited)
     */
    public function getStock(int $productClassId): int|null;

    /**
     * Reduce stock by quantity
     *
     * @return bool True if successful, false if insufficient stock
     */
    public function reduceStock(int $productClassId, int $quantity): bool;

    /**
     * Add stock (e.g., for returns)
     */
    public function addStock(int $productClassId, int $quantity): void;

    /**
     * Set stock level directly
     */
    public function setStock(int $productClassId, int|null $stock, bool $unlimited = false): void;
}
