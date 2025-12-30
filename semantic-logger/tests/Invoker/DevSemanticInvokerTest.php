<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Invoker;

use BEAR\SemanticLogger\Fake\FakeInvoker;
use BEAR\SemanticLogger\Fake\FakeRequest;
use BEAR\SemanticLogger\Fake\FakeResourceObject;
use BEAR\SemanticLogger\Fake\FakeSemanticLogger;
use BEAR\SemanticLogger\Profile\Compact\ContextFactory;
use Koriym\SemanticLogger\DevLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DevSemanticInvokerTest extends TestCase
{
    private DevSemanticInvoker $invoker;
    private FakeSemanticLogger $logger;
    private DevLogger $devLogger;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/semantic-logger-test-' . uniqid();
        mkdir($this->tempDir);

        $this->logger = new FakeSemanticLogger();
        $this->devLogger = new DevLogger($this->tempDir);
        $this->invoker = new DevSemanticInvoker(
            new FakeInvoker(),
            $this->logger,
            $this->devLogger,
            new ContextFactory(),
        );
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        $files = glob($this->tempDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
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

        // Use allLogs because flush() is called in finally block
        $this->assertCount(2, $this->logger->allLogs);
        $this->assertSame('open', $this->logger->allLogs[0]['type']);
        $this->assertSame('close', $this->logger->allLogs[1]['type']);
    }

    public function testInvokePersistsToFile(): void
    {
        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $this->invoker->invoke($request);

        $files = glob($this->tempDir . '/*.json');
        $this->assertNotEmpty($files);
    }

    public function testInvokeWithException(): void
    {
        $invoker = new DevSemanticInvoker(
            new class extends FakeInvoker {
                public function invoke(\BEAR\Resource\AbstractRequest $request): \BEAR\Resource\ResourceObject
                {
                    throw new RuntimeException('Test error');
                }
            },
            $this->logger,
            $this->devLogger,
            new ContextFactory(),
        );

        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        try {
            $invoker->invoke($request);
        } finally {
            // Should still have logged (use allLogs because flush() is called)
            $this->assertCount(2, $this->logger->allLogs);
        }
    }

    public function testInvokeCloseReferencesOpenId(): void
    {
        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        $this->invoker->invoke($request);

        // Use allLogs because flush() is called in finally block
        $this->assertSame('open-1', $this->logger->allLogs[1]['id']);
    }

    public function testInvokeWithExtractor(): void
    {
        $extracted = [];
        $extractor = new class ($extracted) implements \BEAR\SemanticLogger\EventExtractorInterface {
            /** @param array<array{open: \BEAR\SemanticLogger\Context\AbstractOpenContext, complete: \BEAR\SemanticLogger\Context\AbstractCompleteContext}> $extracted */
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private array &$extracted,
            )
            {
            }

            public function extract(
                \BEAR\SemanticLogger\Context\AbstractOpenContext $open,
                \BEAR\SemanticLogger\Context\AbstractCompleteContext $complete,
            ): void {
                $this->extracted[] = ['open' => $open, 'complete' => $complete];
            }
        };

        $invoker = new DevSemanticInvoker(
            new FakeInvoker(),
            $this->logger,
            $this->devLogger,
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

            public function close(\Koriym\SemanticLogger\AbstractContext $context, string $id): void
            {
                if (! $this->firstClose) {
                    throw new RuntimeException('Logger close failed');
                }

                $this->firstClose = false;
                parent::close($context, $id);
            }
        };

        $invoker = new DevSemanticInvoker(
            new class extends FakeInvoker {
                public function invoke(\BEAR\Resource\AbstractRequest $request): \BEAR\Resource\ResourceObject
                {
                    throw new RuntimeException('Original error');
                }
            },
            $logger,
            $this->devLogger,
            new ContextFactory(),
        );

        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        // Original exception should be thrown, not the logging exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Original error');

        $invoker->invoke($request);
    }

    public function testInvokeWhenFlushThrows(): void
    {
        $logger = new class extends FakeSemanticLogger {
            public function flush(array $links = []): \Koriym\SemanticLogger\LogJson
            {
                throw new RuntimeException('Flush failed');
            }
        };

        $invoker = new DevSemanticInvoker(
            new FakeInvoker(),
            $logger,
            $this->devLogger,
            new ContextFactory(),
        );

        $ro = new FakeResourceObject();
        $request = FakeRequest::create($ro, 'get', ['id' => 1]);

        // Should not throw - flush failure is caught
        $result = $invoker->invoke($request);

        $this->assertInstanceOf(FakeResourceObject::class, $result);
    }
}
