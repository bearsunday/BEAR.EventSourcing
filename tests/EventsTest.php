<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function testCollectionIsImmutableAndFilterable(): void
    {
        $post = Event::create('app://self/users', 'POST', ['name' => 'Ada']);
        $put = Event::create('app://self/users/1', 'PUT', ['name' => 'Grace']);

        $events = (new Events())->add($post)->add($put);

        $this->assertCount(2, $events);
        $this->assertCount(1, $events->filterByMethod('post'));
        $this->assertCount(2, $events->filterByUri('app://self/users*'));
        $this->assertCount(0, (new Events())->all());
    }

    public function testJsonRoundTrip(): void
    {
        $events = new Events([
            Event::create(
                'app://self/users',
                'POST',
                ['name' => 'Ada'],
                ['id' => 1],
                new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'),
            ),
        ]);

        $restored = Events::fromJson($events->toJson());

        $this->assertCount(1, $restored);
        $this->assertSame($events->all()[0]->toArray(), $restored->all()[0]->toArray());
    }

    public function testFromJsonSkipsMalformedEntries(): void
    {
        $events = Events::fromJson('[{"uri":"app://self/users","method":"POST"},{"method":"POST"},"bad"]');

        $this->assertCount(1, $events);
        $this->assertSame('app://self/users', $events->all()[0]->uri);
    }

    public function testReplayCallsHandlerForEachEvent(): void
    {
        $events = new Events([
            Event::create('app://self/users', 'POST'),
            Event::create('app://self/users/1', 'DELETE'),
        ]);
        $methods = [];

        $events->replay(static function (Event $event) use (&$methods): void {
            $methods[] = $event->method;
        });

        $this->assertSame(['POST', 'DELETE'], $methods);
    }
}
