<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Orders;

use BEAR\EventSourcing\Query\OrderItemQueryInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

/**
 * Order items resource (注文明細一覧)
 */
class Items extends ResourceObject
{
    #[Inject]
    public function __construct(private OrderItemQueryInterface $orderItemQuery)
    {
    }

    /**
     * Get order items by order ID
     *
     * @param int $id Order ID
     */
    public function onGet(int $id): static
    {
        $this->body = $this->orderItemQuery->findByOrderId($id);

        return $this;
    }
}
