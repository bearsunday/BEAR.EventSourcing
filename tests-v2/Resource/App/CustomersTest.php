<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BearEccube\Query\Fake\FakeCustomerQuery;
use BearEccube\Resource\App\Customers;
use PHPUnit\Framework\TestCase;

class CustomersTest extends TestCase
{
    private Customers $resource;

    protected function setUp(): void
    {
        $this->resource = new Customers(new FakeCustomerQuery());
    }

    public function testOnGet(): void
    {
        $ro = $this->resource->onGet();

        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('customers', $ro->body);
        $this->assertArrayHasKey('total', $ro->body);
        $this->assertArrayHasKey('limit', $ro->body);
        $this->assertArrayHasKey('offset', $ro->body);
        $this->assertIsArray($ro->body['customers']);
    }

    public function testOnGetWithNameFilter(): void
    {
        $ro = $this->resource->onGet(name: '山田');

        $this->assertSame(200, $ro->code);
        $this->assertCount(1, $ro->body['customers']);
        $this->assertSame('山田', $ro->body['customers'][0]['name01']);
    }

    public function testOnGetWithEmailFilter(): void
    {
        $ro = $this->resource->onGet(email: 'customer@example.com');

        $this->assertSame(200, $ro->code);
        $this->assertGreaterThanOrEqual(1, count($ro->body['customers']));
    }

    public function testCustomerStructure(): void
    {
        $ro = $this->resource->onGet();

        $customer = $ro->body['customers'][0];

        // 必須フィールドの確認
        $this->assertArrayHasKey('id', $customer);
        $this->assertArrayHasKey('name01', $customer);
        $this->assertArrayHasKey('name02', $customer);
        $this->assertArrayHasKey('email', $customer);
        $this->assertArrayHasKey('create_date', $customer);
        $this->assertArrayHasKey('update_date', $customer);

        // リレーションの確認
        $this->assertArrayHasKey('customer_status', $customer);
        $this->assertArrayHasKey('orders', $customer);
    }
}
