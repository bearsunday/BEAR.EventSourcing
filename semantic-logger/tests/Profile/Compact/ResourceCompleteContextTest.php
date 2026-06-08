<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use Koriym\SemanticLogger\AbstractContext;
use PHPUnit\Framework\TestCase;

final class ResourceCompleteContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $context = new ResourceCompleteContext(
            'app://self/user',
            200,
            ['Content-Type' => 'application/json'],
            ['id' => 1, 'name' => 'test'],
            '{"id":1,"name":"test"}',
        );

        $this->assertSame('app://self/user', $context->uri);
        $this->assertSame(200, $context->code);
        $this->assertSame(['Content-Type' => 'application/json'], $context->headers);
        $this->assertSame(['id' => 1, 'name' => 'test'], $context->body);
        $this->assertSame('{"id":1,"name":"test"}', $context->view);
    }

    public function testExtendsAbstractContext(): void
    {
        $context = new ResourceCompleteContext('app://self/user', 200, [], null);

        $this->assertInstanceOf(AbstractContext::class, $context);
    }

    public function testTypeConstant(): void
    {
        $this->assertSame('resource.complete', ResourceCompleteContext::TYPE);
    }

    public function testWithNullView(): void
    {
        $context = new ResourceCompleteContext('app://self/user', 200, [], null);

        $this->assertNull($context->view);
    }
}
