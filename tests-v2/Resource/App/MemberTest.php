<?php

declare(strict_types=1);

namespace BearEccube\Tests\Resource\App;

use BEAR\Resource\Code;
use BearEccube\Query\Fake\FakeMemberQuery;
use BearEccube\Resource\App\Member;
use PHPUnit\Framework\TestCase;

class MemberTest extends TestCase
{
    private Member $resource;

    protected function setUp(): void
    {
        $this->resource = new Member(new FakeMemberQuery());
    }

    public function testOnGetReturnsMember(): void
    {
        $ro = $this->resource->onGet(id: 1);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('管理者', $ro->body['name']);
        $this->assertSame('admin', $ro->body['login_id']);
    }

    public function testOnGetReturnsNotFoundForUnknownId(): void
    {
        $ro = $this->resource->onGet(id: 99999);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }
}
