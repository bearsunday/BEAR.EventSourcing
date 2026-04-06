<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\CustomerQueryInterface;

/**
 * Customer Resource
 *
 * 個別顧客を返すリソース。
 */
class Customer extends ResourceObject
{
    public function __construct(
        private CustomerQueryInterface $query
    ) {
    }

    /**
     * IDで顧客を取得
     */
    public function onGet(int $id): static
    {
        $customer = $this->query->findById($id);

        if ($customer === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => 'Customer not found'];
            return $this;
        }

        $this->body = $customer;

        return $this;
    }
}
