<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use PHPUnit\Framework\TestCase;

final class InMemoryEventStoreTest extends TestCase
{
    private InMemoryEventStore $store;

    protected function setUp(): void
    {
        $this->store = new InMemoryEventStore();
    }

    private function createEvent(string $id, string $uri = 'app://self/user', string $timestamp = '2025-01-01T00:00:00+00:00'): Event
    {
        return new Event(
            id: $id,
            timestamp: $timestamp,
            uri: $uri,
            method: 'POST',
            params: [],
            result: null,
        );
    }

    public function testAppendAndGetAll(): void
    {
        $event1 = $this->createEvent('1');
        $event2 = $this->createEvent('2');

        $this->store->append($event1);
        $this->store->append($event2);

        $events = $this->store->getAllEvents();

        $this->assertCount(2, $events);
    }

    public function testGetEventsByUri(): void
    {
        $this->store->append($this->createEvent('1', 'app://self/user'));
        $this->store->append($this->createEvent('2', 'app://self/post'));
        $this->store->append($this->createEvent('3', 'app://self/user'));

        $events = $this->store->getEvents('app://self/user');

        $this->assertCount(2, $events);
    }

    public function testGetEventsSince(): void
    {
        $this->store->append($this->createEvent('1', 'app://self/user', '2025-01-01T00:00:00+00:00'));
        $this->store->append($this->createEvent('2', 'app://self/user', '2025-01-02T00:00:00+00:00'));
        $this->store->append($this->createEvent('3', 'app://self/user', '2025-01-03T00:00:00+00:00'));

        $events = $this->store->getEventsSince('2025-01-02T00:00:00+00:00');

        $this->assertCount(2, $events);
    }

    public function testClear(): void
    {
        $this->store->append($this->createEvent('1'));
        $this->store->append($this->createEvent('2'));

        $this->store->clear();

        $this->assertCount(0, $this->store->getAllEvents());
    }

    public function testEmptyStore(): void
    {
        $events = $this->store->getAllEvents();

        $this->assertCount(0, $events);
    }
}
