<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Exception;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(int $productClassId, int $requested, int $available)
    {
        parent::__construct(sprintf(
            'Insufficient stock for product class %d: requested %d, available %d',
            $productClassId,
            $requested,
            $available
        ));
    }
}
