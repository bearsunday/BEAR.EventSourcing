<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BEAR\Resource\Code;
use BearEccube\Query\Fake\FakeCustomerQuery;
use BearEccube\Resource\App\Customer;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    private Customer $resource;

    protected function setUp(): void
    {
        $this->resource = new Customer(new FakeCustomerQuery());
    }

    public function testOnGetReturnsCustomer(): void
    {
        $ro = $this->resource->onGet(id: 1);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('山田', $ro->body['name01']);
        $this->assertSame('customer@example.com', $ro->body['email']);
    }

    public function testOnGetReturnsNotFoundForUnknownId(): void
    {
        $ro = $this->resource->onGet(id: 99999);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertArrayHasKey('error', $ro->body);
    }
}
