<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BearEccube\Query\Fake\FakeProductQuery;
use BearEccube\Resource\App\Products;
use PHPUnit\Framework\TestCase;

class ProductsTest extends TestCase
{
    private Products $resource;

    protected function setUp(): void
    {
        // FakeQueryを使用（本物のDBは不要）
        $this->resource = new Products(new FakeProductQuery());
    }

    public function testOnGet(): void
    {
        $ro = $this->resource->onGet();

        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('products', $ro->body);
        $this->assertArrayHasKey('total', $ro->body);
        $this->assertArrayHasKey('limit', $ro->body);
        $this->assertArrayHasKey('offset', $ro->body);
        $this->assertIsArray($ro->body['products']);
    }

    public function testOnGetWithNameFilter(): void
    {
        $ro = $this->resource->onGet(name: 'パーコ');

        $this->assertSame(200, $ro->code);
        $this->assertCount(1, $ro->body['products']);
        $this->assertSame('パーコレーター', $ro->body['products'][0]['name']);
    }

    public function testOnGetWithLimit(): void
    {
        $ro = $this->resource->onGet(limit: 1);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $ro->body['limit']);
    }

    public function testProductStructure(): void
    {
        $ro = $this->resource->onGet();

        $product = $ro->body['products'][0];

        // 必須フィールドの確認
        $this->assertArrayHasKey('id', $product);
        $this->assertArrayHasKey('name', $product);
        $this->assertArrayHasKey('status', $product);
        $this->assertArrayHasKey('create_date', $product);
        $this->assertArrayHasKey('update_date', $product);

        // ネストした構造の確認
        $this->assertArrayHasKey('id', $product['status']);
        $this->assertArrayHasKey('name', $product['status']);
        $this->assertArrayHasKey('classes', $product);
        $this->assertArrayHasKey('images', $product);
        $this->assertArrayHasKey('categories', $product);
    }
}
