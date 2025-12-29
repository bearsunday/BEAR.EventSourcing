<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use Koriym\SemanticLogger\AbstractContext;
use PHPUnit\Framework\TestCase;

final class OpenContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $context = new OpenContext('get', 'app://self/user', ['id' => 1]);

        $this->assertSame('get', $context->method);
        $this->assertSame('app://self/user', $context->uri);
        $this->assertSame(['id' => 1], $context->params);
        $this->assertInstanceOf(AbstractContext::class, $context->context);
    }

    public function testEmptyParams(): void
    {
        $context = new OpenContext('get', 'app://self/user');

        $this->assertSame([], $context->params);
    }

    public function testContextIsResourceOpenContext(): void
    {
        $context = new OpenContext('post', 'app://self/user', ['name' => 'test']);

        $resourceContext = $context->context;
        $this->assertInstanceOf(AbstractContext::class, $resourceContext);
    }
}
