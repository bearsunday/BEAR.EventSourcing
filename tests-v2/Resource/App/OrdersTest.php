<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BearEccube\Query\Fake\FakeOrderQuery;
use BearEccube\Resource\App\Orders;
use PHPUnit\Framework\TestCase;

class OrdersTest extends TestCase
{
    private Orders $resource;

    protected function setUp(): void
    {
        $this->resource = new Orders(new FakeOrderQuery());
    }

    public function testOnGet(): void
    {
        $ro = $this->resource->onGet();

        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('orders', $ro->body);
        $this->assertArrayHasKey('total', $ro->body);
        $this->assertArrayHasKey('limit', $ro->body);
        $this->assertArrayHasKey('offset', $ro->body);
        $this->assertIsArray($ro->body['orders']);
    }

    public function testOnGetWithOrderNoFilter(): void
    {
        $ro = $this->resource->onGet(order_no: 'ORDER-001');

        $this->assertSame(200, $ro->code);
        $this->assertCount(1, $ro->body['orders']);
        $this->assertSame('ORDER-001', $ro->body['orders'][0]['order_no']);
    }

    public function testOrderStructure(): void
    {
        $ro = $this->resource->onGet();

        $order = $ro->body['orders'][0];

        // 必須フィールドの確認
        $this->assertArrayHasKey('id', $order);
        $this->assertArrayHasKey('order_no', $order);
        $this->assertArrayHasKey('name01', $order);
        $this->assertArrayHasKey('name02', $order);
        $this->assertArrayHasKey('total', $order);
        $this->assertArrayHasKey('payment_total', $order);
        $this->assertArrayHasKey('create_date', $order);

        // リレーションの確認
        $this->assertArrayHasKey('customer', $order);
        $this->assertArrayHasKey('order_items', $order);
        $this->assertArrayHasKey('shippings', $order);
        $this->assertArrayHasKey('order_status', $order);
        $this->assertArrayHasKey('payment', $order);
    }
}
