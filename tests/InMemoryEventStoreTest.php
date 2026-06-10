<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\InMemoryEventStore;
use PHPUnit\Framework\TestCase;

final class InMemoryEventStoreTest extends TestCase
{
    public function testAppendStoresEvents(): void
    {
        $store = new InMemoryEventStore();
        $store->append(Event::create('app://self/users', 'POST'));

        $this->assertCount(1, $store->all());
        $this->assertSame('POST', iterator_to_array($store->all())[0]->method);
    }

    public function testAppendAllStoresCollection(): void
    {
        $store = new InMemoryEventStore();
        $events = new Events([
            Event::create('app://self/users', 'POST'),
            Event::create('app://self/users/1', 'DELETE'),
        ]);

        $store->appendAll($events);

        $this->assertCount(2, $store->all());
    }

    public function testCanStartWithExistingEvents(): void
    {
        $store = new InMemoryEventStore(new Events([
            Event::create('app://self/users/1', 'PUT'),
        ]));

        $this->assertCount(1, $store->all());
    }
}
