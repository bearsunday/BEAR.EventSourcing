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
}
