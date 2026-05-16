<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BEAR\Resource\Code;
use BearEccube\Query\Fake\FakeProductQuery;
use BearEccube\Resource\App\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    private Product $resource;

    protected function setUp(): void
    {
        $this->resource = new Product(new FakeProductQuery());
    }

    public function testOnGetReturnsProduct(): void
    {
        $ro = $this->resource->onGet(id: 1);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('サンプル商品', $ro->body['name']);
    }

    public function testOnGetReturnsNotFoundForUnknownId(): void
    {
        $ro = $this->resource->onGet(id: 99999);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertArrayHasKey('error', $ro->body);
    }
}
