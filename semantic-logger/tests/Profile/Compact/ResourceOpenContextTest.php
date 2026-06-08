<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use Koriym\SemanticLogger\AbstractContext;
use PHPUnit\Framework\TestCase;

final class ResourceOpenContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $context = new ResourceOpenContext('get', 'app://self/user', ['id' => 1]);

        $this->assertSame('get', $context->method);
        $this->assertSame('app://self/user', $context->uri);
        $this->assertSame(['id' => 1], $context->params);
    }

    public function testExtendsAbstractContext(): void
    {
        $context = new ResourceOpenContext('get', 'app://self/user');

        $this->assertInstanceOf(AbstractContext::class, $context);
    }

    public function testTypeConstant(): void
    {
        $this->assertSame('resource.open', ResourceOpenContext::TYPE);
    }

    public function testSchemaUrlConstant(): void
    {
        $this->assertSame('', ResourceOpenContext::SCHEMA_URL);
    }

    public function testEmptyParams(): void
    {
        $context = new ResourceOpenContext('get', 'app://self/user');

        $this->assertSame([], $context->params);
    }
}
