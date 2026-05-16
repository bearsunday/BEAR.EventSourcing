<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BEAR\Resource\Code;
use BearEccube\Query\Fake\FakeOrderQuery;
use BearEccube\Resource\App\Order;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    private Order $resource;

    protected function setUp(): void
    {
        $this->resource = new Order(new FakeOrderQuery());
    }

    public function testOnGetReturnsOrder(): void
    {
        $ro = $this->resource->onGet(id: 1);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('ORDER-001', $ro->body['order_no']);
    }

    public function testOnGetReturnsNotFoundForUnknownId(): void
    {
        $ro = $this->resource->onGet(id: 99999);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertArrayHasKey('error', $ro->body);
    }
}
