<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Interceptor;

use BEAR\EventSourcing\EventSourcing\Event;
use BEAR\Resource\Uri;
use PHPUnit\Framework\TestCase;

class EventSourcingInterceptorTest extends TestCase
{
    public function testRecordsExplicitNullParameter(): void
    {
        $eventStore = new RecordingEventStore();
        $interceptor = new EventSourcingInterceptor($eventStore);
        $resource = new NullParameterResource();
        $resource->uri = new Uri('app://self/users/1');

        $result = $interceptor->invoke(new NullParameterInvocation($resource, [null]));

        $this->assertSame($resource, $result);
        $this->assertInstanceOf(Event::class, $eventStore->event);
        $this->assertSame(['memo' => null], $eventStore->event->params);
    }
}
