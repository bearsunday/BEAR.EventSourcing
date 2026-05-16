<?php

declare(strict_types=1);

namespace BearEccube\Module;

use BearEccube\Query\CartQueryInterface;
use BearEccube\Query\CategoryQueryInterface;
use BearEccube\Query\CustomerQueryInterface;
use BearEccube\Query\Fake\FakeCartQuery;
use BearEccube\Query\Fake\FakeCategoryQuery;
use BearEccube\Query\Fake\FakeCustomerQuery;
use BearEccube\Query\Fake\FakeMemberQuery;
use BearEccube\Query\Fake\FakeOrderQuery;
use BearEccube\Query\Fake\FakeProductQuery;
use BearEccube\Query\MemberQueryInterface;
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
        $this->bind(CategoryQueryInterface::class)->to(FakeCategoryQuery::class);
        $this->bind(CartQueryInterface::class)->to(FakeCartQuery::class);
        $this->bind(MemberQueryInterface::class)->to(FakeMemberQuery::class);
    }
}
