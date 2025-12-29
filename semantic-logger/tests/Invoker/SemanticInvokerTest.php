<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Invoker;

use BEAR\SemanticLogger\Fake\FakeInvoker;
use BEAR\SemanticLogger\Fake\FakeRequest;
use BEAR\SemanticLogger\Fake\FakeResourceObject;
use BEAR\SemanticLogger\Fake\FakeSemanticLogger;
use BEAR\SemanticLogger\Profile\Compact\ContextFactory;
use PHPUnit\Framework\TestCase;

final class SemanticInvokerTest extends TestCase
{
    private SemanticInvoker $invoker;
    private FakeSemanticLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new FakeSemanticLogger();
        $this->invoker = new SemanticInvoker(
            new FakeInvoker(),
            $this->logger,
            new ContextFactory(),
        );
    }

    public function testInvoke(): void
    {
        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $result = $this->invoker->invoke($request);

        $this->assertInstanceOf(FakeResourceObject::class, $result);
        $this->assertSame(['id' => 1, 'name' => 'test'], $result->body);
    }

    public function testInvokeLogsOpenAndClose(): void
    {
        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $this->invoker->invoke($request);

        $this->assertCount(2, $this->logger->logs);
        $this->assertSame('open', $this->logger->logs[0]['type']);
        $this->assertSame('close', $this->logger->logs[1]['type']);
    }

    public function testInvokeCloseReferencesOpenId(): void
    {
        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $this->invoker->invoke($request);

        $this->assertSame('open-1', $this->logger->logs[1]['id']);
    }

    public function testInvokeWithException(): void
    {
        $invoker = new SemanticInvoker(
            new class extends FakeInvoker {
                public function invoke(\BEAR\Resource\AbstractRequest $request): \BEAR\Resource\ResourceObject
                {
                    throw new \RuntimeException('Test error');
                }
            },
            $this->logger,
            new ContextFactory(),
        );

        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        try {
            $invoker->invoke($request);
        } finally {
            // Should still have logged open and error close
            $this->assertCount(2, $this->logger->logs);
            $this->assertSame('open', $this->logger->logs[0]['type']);
            $this->assertSame('close', $this->logger->logs[1]['type']);
        }
    }
}
