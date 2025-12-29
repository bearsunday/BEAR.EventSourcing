<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use BEAR\SemanticLogger\Fake\FakeRequest;
use BEAR\SemanticLogger\Fake\FakeResourceObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContextFactoryTest extends TestCase
{
    private ContextFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ContextFactory();
    }

    public function testCreateOpenContext(): void
    {
        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $context = $this->factory->createOpenContext($request);

        $this->assertInstanceOf(OpenContext::class, $context);
        $this->assertSame('get', $context->method);
        $this->assertSame('app://self/user', $context->uri);
        $this->assertSame(['id' => 1], $context->params);
    }

    public function testCreateCompleteContext(): void
    {
        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);
        $openContext = $this->factory->createOpenContext($request);

        $completeContext = $this->factory->createCompleteContext($ro, $openContext);

        $this->assertInstanceOf(CompleteContext::class, $completeContext);
        $this->assertSame('app://self/user', $completeContext->uri);
        $this->assertSame(200, $completeContext->code);
        $this->assertSame(['Content-Type' => 'application/json'], $completeContext->headers);
    }

    public function testCreateErrorContext(): void
    {
        $exception = new RuntimeException('Test error');

        $context = $this->factory->createErrorContext($exception);

        $this->assertInstanceOf(ErrorContext::class, $context);
        $this->assertSame($exception, $context->exception);
    }

    public function testCreateErrorContextWithOpenContext(): void
    {
        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);
        $openContext = $this->factory->createOpenContext($request);
        $exception = new RuntimeException('Test error');

        $errorContext = $this->factory->createErrorContext($exception, $openContext);

        $this->assertInstanceOf(ErrorContext::class, $errorContext);
    }
}
