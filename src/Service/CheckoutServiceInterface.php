<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Service;

use RuntimeException;

/**
 * Checkout service interface
 */
interface CheckoutServiceInterface
{
    /**
     * Preview checkout (calculate totals without creating order)
     *
     * @param array<string, mixed> $cart Cart data
     *
     * @return array<string, mixed> Preview data with totals
     */
    public function preview(array $cart): array;

    /**
     * Complete checkout and create order
     *
     * @param array<string, mixed> $data Checkout data
     *
     * @return int Created order ID
     *
     * @throws RuntimeException If checkout fails
     */
    public function complete(array $data): int;
}
