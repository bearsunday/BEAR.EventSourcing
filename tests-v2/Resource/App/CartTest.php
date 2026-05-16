<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BEAR\Resource\Code;
use BearEccube\Query\Fake\FakeCartQuery;
use BearEccube\Resource\App\Cart;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    private Cart $resource;

    protected function setUp(): void
    {
        $this->resource = new Cart(new FakeCartQuery());
    }

    public function testOnGetReturnsCart(): void
    {
        $ro = $this->resource->onGet(id: 1);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame(1000, $ro->body['total_price']);
    }

    public function testOnGetReturnsNotFoundForUnknownId(): void
    {
        $ro = $this->resource->onGet(id: 99999);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
