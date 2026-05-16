<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BearEccube\Query\Fake\FakeCategoryQuery;
use BearEccube\Resource\App\Categories;
use PHPUnit\Framework\TestCase;

class CategoriesTest extends TestCase
{
    private Categories $resource;

    protected function setUp(): void
    {
        $this->resource = new Categories(new FakeCategoryQuery());
    }

    public function testOnGet(): void
    {
        $ro = $this->resource->onGet();

        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('categories', $ro->body);
        $this->assertArrayHasKey('total', $ro->body);
        $this->assertIsArray($ro->body['categories']);
    }

    public function testOnGetWithNameFilter(): void
    {
        $ro = $this->resource->onGet(name: 'カテゴリ');

        $this->assertSame(200, $ro->code);
        $this->assertCount(1, $ro->body['categories']);
    }
}
