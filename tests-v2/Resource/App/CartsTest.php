<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BearEccube\Query\Fake\FakeCartQuery;
use BearEccube\Resource\App\Carts;
use PHPUnit\Framework\TestCase;

class CartsTest extends TestCase
{
    private Carts $resource;

    protected function setUp(): void
    {
        $this->resource = new Carts(new FakeCartQuery());
    }

    public function testOnGet(): void
    {
        $ro = $this->resource->onGet();

        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('carts', $ro->body);
        $this->assertArrayHasKey('total', $ro->body);
        $this->assertIsArray($ro->body['carts']);
    }
}
