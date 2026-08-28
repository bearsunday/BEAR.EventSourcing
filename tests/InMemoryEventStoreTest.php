<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\Store\InMemoryEventStore;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InMemoryEventStoreTest extends TestCase
{
    public function testAppendStoresEvents(): void
    {
        $store = new InMemoryEventStore();
        $store->append(self::event('app://self/users', 'POST'));

        $this->assertCount(1, $store->all());
        $this->assertSame('POST', iterator_to_array($store->all())[0]->method);
    }

    public function testAppendAllStoresCollection(): void
    {
        $store = new InMemoryEventStore();
        $events = new Events([
            self::event('app://self/users', 'POST'),
            self::event('app://self/users/1', 'DELETE'),
        ]);

        $store->appendAll($events);

        $this->assertCount(2, $store->all());
    }

    public function testCanStartWithExistingEvents(): void
    {
        $store = new InMemoryEventStore(new Events([
            self::event('app://self/users/1', 'PUT'),
        ]));

        $this->assertCount(1, $store->all());
    }

    public function testAppendIsIdempotentPerEventId(): void
    {
        $store = new InMemoryEventStore();
        $event = self::event('app://self/users', 'POST');

        $store->append($event);
        $store->append($event);
        $store->appendAll(new Events([$event]));

        $this->assertCount(1, $store->all());
    }

    private static function event(string $uri, string $method): Event
    {
        return new Event($uri, $method, new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'));
    }
}
