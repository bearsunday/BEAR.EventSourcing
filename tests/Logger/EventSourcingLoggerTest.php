<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Logger;

use BEAR\EventSourcing\EventSourcing\Event;
use BEAR\Resource\Uri;
use PHPUnit\Framework\TestCase;

class EventSourcingLoggerTest extends TestCase
{
    public function testRecordsStateChangingResourceLog(): void
    {
        $eventStore = new RecordingEventStore();
        $resourceLogger = new RecordingResourceLogger();
        $logger = new EventSourcingLogger($eventStore, $resourceLogger);
        $resource = $this->resource('post', ['name' => 'Ada', 'memo' => null], ['id' => 1]);

        $logger($resource);

        $this->assertInstanceOf(Event::class, $eventStore->event);
        $this->assertSame('app://self/users/1', $eventStore->event->uri);
        $this->assertSame('POST', $eventStore->event->method);
        $this->assertSame(['name' => 'Ada', 'memo' => null], $eventStore->event->params);
        $this->assertSame(['id' => 1], $eventStore->event->result);
        $this->assertSame([$resource], $resourceLogger->resources);
    }

    public function testDoesNotRecordGet(): void
    {
        $eventStore = new RecordingEventStore();
        $resourceLogger = new RecordingResourceLogger();
        $logger = new EventSourcingLogger($eventStore, $resourceLogger);
        $resource = $this->resource('get', ['id' => 1], ['id' => 1]);

        $logger($resource);

        $this->assertNull($eventStore->event);
        $this->assertSame([$resource], $resourceLogger->resources);
    }

    public function testDoesNotRecordPatch(): void
    {
        $eventStore = new RecordingEventStore();
        $resourceLogger = new RecordingResourceLogger();
        $logger = new EventSourcingLogger($eventStore, $resourceLogger);
        $resource = $this->resource('patch', ['name' => 'Ada'], ['id' => 1]);

        $logger($resource);

        $this->assertNull($eventStore->event);
        $this->assertSame([$resource], $resourceLogger->resources);
    }

    /** @param array<string, mixed> $query */
    private function resource(string $method, array $query, mixed $body): LoggerResource
    {
        $resource = new LoggerResource();
        $resource->uri = new Uri('app://self/users/1', $query);
        $resource->uri->method = $method;
        $resource->body = $body;

        return $resource;
    }
}
