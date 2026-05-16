<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BearEccube\Query\Fake\FakeMemberQuery;
use BearEccube\Resource\App\Members;
use PHPUnit\Framework\TestCase;

class MembersTest extends TestCase
{
    private Members $resource;

    protected function setUp(): void
    {
        $this->resource = new Members(new FakeMemberQuery());
    }

    public function testOnGet(): void
    {
        $ro = $this->resource->onGet();

        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey('members', $ro->body);
        $this->assertArrayHasKey('total', $ro->body);
        $this->assertIsArray($ro->body['members']);
    }

    public function testOnGetWithNameFilter(): void
    {
        $ro = $this->resource->onGet(name: '管理');

        $this->assertSame(200, $ro->code);
        $this->assertCount(1, $ro->body['members']);
    }
}
