<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use BEAR\EventSourcing\Fake\FakeAppModule;
use BEAR\Resource\ResourceInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function array_map;
use function is_dir;
use function mkdir;
use function sys_get_temp_dir;
use function uniqid;

/**
 * Real-machine verification: wire a BEAR.Resource client through a Ray.Di
 * injector with SemanticLoggerModule installed, make actual resource
 * requests, and confirm the bridge records them — proving the
 * LoggerInterface -> SemanticLogger binding works end to end.
 */
final class ResourceClientIntegrationTest extends TestCase
{
    private ResourceInterface|null $resource = null;
    private SemanticLoggerInterface|null $semanticLogger = null;

    protected function setUp(): void
    {
        $tmpDir = sys_get_temp_dir() . '/bear-es-' . uniqid();
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $injector = new Injector(new FakeAppModule(), $tmpDir);
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->semanticLogger = $injector->getInstance(SemanticLoggerInterface::class);
    }

    public function testPostIsRecordedThroughTheResourceClient(): void
    {
        $resource = $this->resource;
        $semanticLogger = $this->semanticLogger;
        $this->assertInstanceOf(ResourceInterface::class, $resource);
        $this->assertInstanceOf(SemanticLoggerInterface::class, $semanticLogger);

        $ro = $resource->post('app://self/users', ['name' => 'Alice', 'age' => 30]);
        $this->assertSame(['name' => 'Alice', 'age' => 30], $ro->body);

        $events = Events::fromSemanticLog($semanticLogger->flush()->toArray());

        $this->assertCount(1, $events);
        $event = $events->all()[0];
        $this->assertSame('POST', $event->method);
        $this->assertSame(['name' => 'Alice', 'age' => 30], $event->params);
        $this->assertSame(['name' => 'Alice', 'age' => 30], $event->result);
    }

    public function testGetIsNotRecorded(): void
    {
        $resource = $this->resource;
        $semanticLogger = $this->semanticLogger;
        $this->assertInstanceOf(ResourceInterface::class, $resource);
        $this->assertInstanceOf(SemanticLoggerInterface::class, $semanticLogger);

        $resource->get('app://self/users', ['id' => 7]);
        $resource->post('app://self/users', ['name' => 'Bob', 'age' => 25]);

        $events = Events::fromSemanticLog($semanticLogger->flush()->toArray());

        $methods = array_map(static fn (Event $e): string => $e->method, $events->all());
        $this->assertSame(['POST'], $methods);
    }
}
