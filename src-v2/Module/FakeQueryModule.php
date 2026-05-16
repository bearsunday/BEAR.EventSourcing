<?php

declare(strict_types=1);

namespace BearEccube\Module;

use BearEccube\Query\CustomerQueryInterface;
use BearEccube\Query\Fake\FakeCustomerQuery;
use BearEccube\Query\Fake\FakeOrderQuery;
use BearEccube\Query\Fake\FakeProductQuery;
use BearEccube\Query\OrderQueryInterface;
use BearEccube\Query\ProductQueryInterface;
use Ray\Di\AbstractModule;

/**
 * Fake Query Module
 *
 * 開発・テスト用。全てのQueryをFake実装にバインド。
 * 本番はProdQueryModuleを使用。
 */
class FakeQueryModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(ProductQueryInterface::class)->to(FakeProductQuery::class);
        $this->bind(CustomerQueryInterface::class)->to(FakeCustomerQuery::class);
        $this->bind(OrderQueryInterface::class)->to(FakeOrderQuery::class);
    }
}
