<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\OrderQueryInterface;

/**
 * Order Resource
 *
 * 個別注文を返すリソース。
 */
class Order extends ResourceObject
{
    public function __construct(
        private OrderQueryInterface $query
    ) {
    }

    /**
     * IDで注文を取得
     */
    public function onGet(int $id): static
    {
        $order = $this->query->findById($id);

        if ($order === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => 'Order not found'];
            return $this;
        }

        $this->body = $order;

        return $this;
    }
}
