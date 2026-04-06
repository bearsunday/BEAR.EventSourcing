<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\OrderQueryInterface;

/**
 * Orders Resource
 *
 * 注文一覧を返すリソース。
 */
class Orders extends ResourceObject
{
    public function __construct(
        private OrderQueryInterface $query
    ) {
    }

    /**
     * 注文一覧を取得
     */
    public function onGet(
        ?int $customer_id = null,
        ?int $status_id = null,
        ?string $order_no = null,
        int $limit = 20,
        int $offset = 0
    ): static {
        $this->body = $this->query->findList($customer_id, $status_id, $order_no, $limit, $offset);

        return $this;
    }
}
