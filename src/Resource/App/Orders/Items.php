<?php

declare(strict_types=1);

namespace BearEccube\Resource\App\Orders;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\OrderItemQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Order items resource (注文明細一覧)
 */
class Items extends ResourceObject
{
    private OrderItemQueryInterface $orderItemQuery;

    #[Inject]
    public function __construct(OrderItemQueryInterface $orderItemQuery)
    {
        $this->orderItemQuery = $orderItemQuery;
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
