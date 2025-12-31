<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\OrderQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Orders resource (注文一覧)
 *
 * @Link(rel="order", href="/orders/{id}")
 */
class Orders extends ResourceObject
{
    private OrderQueryInterface $orderQuery;

    #[Inject]
    public function __construct(OrderQueryInterface $orderQuery)
    {
        $this->orderQuery = $orderQuery;
    }

    /**
     * Get order list
     *
     * @param int|null    $customerId Customer ID to filter
     * @param int|null    $status     Order status ID to filter
     * @param string|null $orderNo    Order number to search
     * @param string|null $dateFrom   Order date from (Y-m-d)
     * @param string|null $dateTo     Order date to (Y-m-d)
     * @param int         $page       Page number
     * @param int         $limit      Items per page
     */
    public function onGet(
        ?int $customerId = null,
        ?int $status = null,
        ?string $orderNo = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $page = 1,
        int $limit = 20
    ): static {
        $offset = ($page - 1) * $limit;

        $this->body = [
            'orders' => $this->orderQuery->findAll(
                $customerId,
                $status,
                $orderNo,
                $dateFrom,
                $dateTo,
                $limit,
                $offset
            ),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $this->orderQuery->count($customerId, $status, $orderNo, $dateFrom, $dateTo),
            ],
        ];

        return $this;
    }
}
