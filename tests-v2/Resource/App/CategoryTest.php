<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BEAR\Resource\Code;
use BearEccube\Query\Fake\FakeCategoryQuery;
use BearEccube\Resource\App\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private Category $resource;

    protected function setUp(): void
    {
        $this->resource = new Category(new FakeCategoryQuery());
    }

    public function testOnGetReturnsCategory(): void
    {
        $ro = $this->resource->onGet(id: 1);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('カテゴリ1', $ro->body['name']);
    }

    public function testOnGetReturnsNotFoundForUnknownId(): void
    {
        $ro = $this->resource->onGet(id: 99999);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
