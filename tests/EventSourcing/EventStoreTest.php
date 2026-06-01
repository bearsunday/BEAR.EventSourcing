<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use Aura\Sql\ExtendedPdo;
use JsonException;
use PHPUnit\Framework\TestCase;

class EventStoreTest extends TestCase
{
    public function testAppendAndGetEvents(): void
    {
        [$eventStore] = $this->newEventStore();
        $event = Event::create('/users/1', 'POST', ['name' => 'John'], ['id' => 1]);

        $eventStore->append($event);

        $events = $eventStore->getEvents();
        $storedEvents = $events->all();

        $this->assertCount(1, $events);
        $this->assertSame($event->id, $storedEvents[0]->id);
        $this->assertSame('/users/1', $storedEvents[0]->uri);
        $this->assertSame('POST', $storedEvents[0]->method);
        $this->assertSame(['name' => 'John'], $storedEvents[0]->params);
        $this->assertSame(['id' => 1], $storedEvents[0]->result);
    }

    public function testInvalidStoredJsonThrows(): void
    {
        [$eventStore, $pdo] = $this->newEventStore();

        $pdo->perform(
            'INSERT INTO event_store (id, timestamp, uri, method, params, result) VALUES (:id, :timestamp, :uri, :method, :params, :result)',
            [
                'id' => 'invalid-json',
                'timestamp' => '2025-01-01 00:00:00.000000',
                'uri' => '/users/1',
                'method' => 'POST',
                'params' => '{',
                'result' => 'null',
            ],
        );

        $this->expectException(JsonException::class);

        $eventStore->getEvents();
    }

    /** @return array{EventStore, ExtendedPdo} */
    private function newEventStore(): array
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        $pdo->exec(
            'CREATE TABLE event_store (
                id TEXT PRIMARY KEY,
                timestamp TEXT NOT NULL,
                uri TEXT NOT NULL,
                method TEXT NOT NULL,
                params TEXT,
                result TEXT
            )',
        );

        return [new EventStore($pdo), $pdo];
    }
}
