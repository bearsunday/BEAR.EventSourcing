<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Invoker;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use BEAR\SemanticLogger\Context\AbstractCompleteContext;
use BEAR\SemanticLogger\Context\AbstractOpenContext;
use BEAR\SemanticLogger\EventExtractorInterface;
use BEAR\SemanticLogger\Fake\FakeInvoker;
use BEAR\SemanticLogger\Fake\FakeRequest;
use BEAR\SemanticLogger\Fake\FakeResourceObject;
use BEAR\SemanticLogger\Fake\FakeSemanticLogger;
use BEAR\SemanticLogger\Profile\Compact\ContextFactory;
use Koriym\SemanticLogger\AbstractContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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
                public function invoke(AbstractRequest $request): ResourceObject
                {
                    throw new RuntimeException('Test error');
                }
            },
            $this->logger,
            new ContextFactory(),
        );

        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $this->expectException(RuntimeException::class);
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

    public function testInvokeWithExtractor(): void
    {
        $extracted = [];
        $extractor = new class ($extracted) implements EventExtractorInterface {
            /** @param array<array{open: AbstractOpenContext, complete: AbstractCompleteContext}> $extracted */
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private array &$extracted,
            ) {
            }

            public function extract(AbstractOpenContext $open, AbstractCompleteContext $complete): void
            {
                $this->extracted[] = ['open' => $open, 'complete' => $complete];
            }
        };

        $invoker = new SemanticInvoker(
            new FakeInvoker(),
            $this->logger,
            new ContextFactory(),
            $extractor,
        );

        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $invoker->invoke($request);

        $this->assertCount(1, $extracted);
        $this->assertSame('get', $extracted[0]['open']->method);
    }

    public function testInvokeWithExceptionWhenLoggerCloseThrows(): void
    {
        $logger = new class extends FakeSemanticLogger {
            private bool $firstClose = true;

            public function close(AbstractContext $context, string $id): void
            {
                if (! $this->firstClose) {
                    throw new RuntimeException('Logger close failed');
                }

                $this->firstClose = false;

                parent::close($context, $id);
            }
        };

        $invoker = new SemanticInvoker(
            new class extends FakeInvoker {
                public function invoke(AbstractRequest $request): ResourceObject
                {
                    throw new RuntimeException('Original error');
                }
            },
            $logger,
            new ContextFactory(),
        );

        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        // Original exception should be thrown, not the logging exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Original error');

        $invoker->invoke($request);
    }
}
