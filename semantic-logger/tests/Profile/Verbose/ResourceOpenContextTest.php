<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\PhpProfile;
use Koriym\SemanticLogger\Profiler\XdebugTrace;
use Koriym\SemanticLogger\Profiler\XHProfResult;
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

    public function testHasPhpProfile(): void
    {
        $context = new ResourceOpenContext('get', 'app://self/user');

        $this->assertInstanceOf(PhpProfile::class, $context->phpProfile);
    }

    public function testHasXhprofResult(): void
    {
        $context = new ResourceOpenContext('get', 'app://self/user');

        $this->assertInstanceOf(XHProfResult::class, $context->xhprofResult);
    }

    public function testHasXdebugTrace(): void
    {
        $context = new ResourceOpenContext('get', 'app://self/user');

        $this->assertInstanceOf(XdebugTrace::class, $context->xdebugTrace);
    }

    public function testWithCallSignature(): void
    {
        $context = new ResourceOpenContext('get', 'app://self/user', [], 'App\Resource::onGet');

        $this->assertSame('App\Resource::onGet', $context->callSignature);
    }

    public function testTypeConstant(): void
    {
        $this->assertSame('resource.open', ResourceOpenContext::TYPE);
    }
}
