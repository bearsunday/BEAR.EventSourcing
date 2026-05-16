<?php

declare(strict_types=1);

namespace BearEccube\Tests\Query\Fake;

use BearEccube\Query\Fake\FakeOrderQuery;
use PHPUnit\Framework\TestCase;

class FakeOrderQueryTest extends TestCase
{
    private FakeOrderQuery $query;

    protected function setUp(): void
    {
        $this->query = new FakeOrderQuery();
    }

    public function testFindByOrderNoReturnsOrder(): void
    {
        $order = $this->query->findByOrderNo('ORDER-001');

        $this->assertNotNull($order);
        $this->assertSame('ORDER-001', $order['order_no']);
    }

    public function testFindByOrderNoReturnsNullForUnknown(): void
    {
        $this->assertNull($this->query->findByOrderNo('UNKNOWN-NO'));
    }

    public function testFindByIdReturnsNullForUnknown(): void
    {
        $this->assertNull($this->query->findById(99999));
    }
}
