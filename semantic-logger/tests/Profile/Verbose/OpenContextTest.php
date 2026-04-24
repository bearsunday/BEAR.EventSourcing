<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\PhpProfile;
use Koriym\SemanticLogger\Profiler\XdebugTrace;
use Koriym\SemanticLogger\Profiler\XHProfResult;
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

    public function testWithCallSignature(): void
    {
        $context = new OpenContext('get', 'app://self/user', [], 'App\Resource\App\User::onGet');

        $this->assertSame('App\Resource\App\User::onGet', $context->callSignature);
    }

    public function testHasPhpProfile(): void
    {
        $context = new OpenContext('get', 'app://self/user');

        $this->assertInstanceOf(PhpProfile::class, $context->phpProfile);
    }

    public function testHasXhprofResult(): void
    {
        $context = new OpenContext('get', 'app://self/user');

        $this->assertInstanceOf(XHProfResult::class, $context->xhprofResult);
    }

    public function testHasXdebugTrace(): void
    {
        $context = new OpenContext('get', 'app://self/user');

        $this->assertInstanceOf(XdebugTrace::class, $context->xdebugTrace);
    }

    public function testEmptyParams(): void
    {
        $context = new OpenContext('get', 'app://self/user');

        $this->assertSame([], $context->params);
    }
}
