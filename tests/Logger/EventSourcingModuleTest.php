<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Logger;

use BEAR\Resource\LoggerInterface as ResourceLoggerInterface;
use BEAR\Resource\Uri;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

class EventSourcingModuleTest extends TestCase
{
    public function testDecoratesExistingResourceLogger(): void
    {
        $eventStore = new RecordingEventStore();
        $resourceLogger = new RecordingResourceLogger();
        $module = new EventSourcingTestModule(new BaseLoggerModule($resourceLogger), $eventStore);
        $logger = (new Injector($module))->getInstance(ResourceLoggerInterface::class);
        $resource = new LoggerResource();
        $resource->uri = new Uri('app://self/users/1', ['name' => 'Ada']);
        $resource->uri->method = 'post';
        $resource->body = ['id' => 1];

        $logger($resource);

        $this->assertNotNull($eventStore->event);
        $this->assertSame('POST', $eventStore->event->method);
        $this->assertSame([$resource], $resourceLogger->resources);
    }
}
