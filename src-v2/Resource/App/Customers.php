<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\CustomerQueryInterface;

/**
 * Customers Resource
 *
 * 顧客一覧を返すリソース。
 */
class Customers extends ResourceObject
{
    public function __construct(
        private CustomerQueryInterface $query
    ) {
    }

    /**
     * 顧客一覧を取得
     */
    public function onGet(
        ?string $name = null,
        ?string $email = null,
        ?int $status_id = null,
        int $limit = 20,
        int $offset = 0
    ): static {
        $this->body = $this->query->findList($name, $email, $status_id, $limit, $offset);

        return $this;
    }
}
