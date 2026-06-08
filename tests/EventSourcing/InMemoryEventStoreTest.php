<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class InMemoryEventStoreTest extends TestCase
{
    public function testAppendAndGetEvents(): void
    {
        $eventStore = new InMemoryEventStore();
        $event = Event::create('/users/1', 'POST', ['name' => 'John'], ['id' => 1]);

        $eventStore->append($event);

        $storedEvents = $eventStore->getEvents()->all();

        $this->assertCount(1, $storedEvents);
        $this->assertSame($event, $storedEvents[0]);
    }

    public function testGetEventsSince(): void
    {
        $eventStore = new InMemoryEventStore(new Events([
            Event::fromArray([
                'id' => 'event-1',
                'timestamp' => '2025-01-01 00:00:00.000000',
                'uri' => '/users/1',
                'method' => 'POST',
            ]),
            Event::fromArray([
                'id' => 'event-2',
                'timestamp' => '2025-01-02 00:00:00.000000',
                'uri' => '/users/2',
                'method' => 'POST',
            ]),
        ]));

        $events = $eventStore->getEventsSince(new DateTimeImmutable('2025-01-02 00:00:00.000000'));

        $this->assertSame(['/users/2'], $this->uris($events));
    }

    public function testGetEventsByUri(): void
    {
        $eventStore = new InMemoryEventStore(new Events([
            Event::create('/users/1', 'POST', [], null),
            Event::create('/users/2', 'POST', [], null),
            Event::create('/orders/1', 'POST', [], null),
        ]));

        $events = $eventStore->getEventsByUri('/users/*');

        $this->assertSame(['/users/1', '/users/2'], $this->uris($events));
    }

    public function testGetEventsByAggregateId(): void
    {
        $eventStore = new InMemoryEventStore(new Events([
            Event::create('/orders/123', 'POST', [], null),
            Event::create('/orders/123/items/1', 'POST', [], null),
            Event::create('/orders/1234', 'POST', [], null),
        ]));

        $events = $eventStore->getEventsByAggregateId('orders', '123');

        $this->assertSame(['/orders/123', '/orders/123/items/1'], $this->uris($events));
    }

    /** @return list<string> */
    private function uris(EventsInterface $events): array
    {
        $uris = [];
        foreach ($events as $event) {
            $uris[] = $event->uri;
        }

        return $uris;
    }
}
