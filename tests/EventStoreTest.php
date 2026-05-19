<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Aura\Sql\ExtendedPdo;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EventStoreTest extends TestCase
{
    private ExtendedPdo $pdo;
    private EventStore $store;

    protected function setUp(): void
    {
        $this->pdo = new ExtendedPdo('sqlite::memory:');
        $this->pdo->exec(
            <<<'SQL'
            CREATE TABLE event_store (
                id TEXT PRIMARY KEY,
                timestamp TEXT NOT NULL,
                uri TEXT NOT NULL,
                method TEXT NOT NULL,
                params TEXT,
                result TEXT
            )
        SQL
        );

        $this->store = new EventStore($this->pdo);
    }

    public function testAppendAllPersistsAllEvents(): void
    {
        $events = new Events([
            Event::create('/a', 'POST', [], null),
            Event::create('/b', 'PUT', [], null),
            Event::create('/c', 'DELETE', [], null),
        ]);

        $this->store->appendAll($events);

        $this->assertCount(3, $this->store->getEvents());
    }

    public function testAppendAllRollsBackOnFailure(): void
    {
        $a = Event::create('/a', 'POST', [], null);
        $duplicate = Event::fromArray([
            'id' => $a->id,
            'timestamp' => '2025-01-01T00:00:00.000000+00:00',
            'uri' => '/dup',
            'method' => 'POST',
        ]);

        $events = new Events([
            Event::create('/x', 'POST', [], null),
            Event::create('/y', 'POST', [], null),
            $a,
            $duplicate,
        ]);

        try {
            $this->store->appendAll($events);
            $this->fail('Expected exception on duplicate id');
        } catch (\Throwable) {
            // expected
        }

        $this->assertCount(0, $this->store->getEvents(), 'Failed batch should be rolled back');
    }

    public function testAppendAndGetEventsRoundTrip(): void
    {
        $event = Event::create('/users/1', 'POST', ['name' => 'a'], ['id' => 1]);
        $this->store->append($event);

        $events = $this->store->getEvents();

        $this->assertCount(1, $events);
        $stored = $events->all()[0];
        $this->assertSame($event->id, $stored->id);
        $this->assertSame('/users/1', $stored->uri);
        $this->assertSame(['name' => 'a'], $stored->params);
        $this->assertSame(['id' => 1], $stored->result);
    }

    public function testGetEventsSinceFilters(): void
    {
        $this->insertWithTimestamp('a', '/a', '2025-01-01T00:00:00+00:00');
        $this->insertWithTimestamp('b', '/b', '2025-06-01T00:00:00+00:00');
        $this->insertWithTimestamp('c', '/c', '2025-12-01T00:00:00+00:00');

        $events = $this->store->getEventsSince(new DateTimeImmutable('2025-05-01T00:00:00+00:00'));

        $this->assertCount(2, $events);
    }

    public function testGetEventsSinceNormalizesTimezone(): void
    {
        $this->insertWithTimestamp('a', '/a', '2025-06-01T00:00:00.000000+00:00');

        $sinceInOtherZone = new DateTimeImmutable('2025-06-01T05:30:00.000000+05:30');
        $events = $this->store->getEventsSince($sinceInOtherZone);

        $this->assertCount(1, $events);
    }

    public function testGetEventsByUriGlob(): void
    {
        $this->store->append(Event::create('/users/1', 'POST', [], null));
        $this->store->append(Event::create('/users/2', 'POST', [], null));
        $this->store->append(Event::create('/orders/1', 'POST', [], null));

        $this->assertCount(2, $this->store->getEventsByUri('/users/*'));
        $this->assertCount(1, $this->store->getEventsByUri('/orders/?'));
    }

    public function testGetEventsByUriEscapesLikeMetacharacters(): void
    {
        $this->store->append(Event::create('/foo_bar', 'POST', [], null));
        $this->store->append(Event::create('/foo-bar', 'POST', [], null));
        $this->store->append(Event::create('/fooXbar', 'POST', [], null));

        $events = $this->store->getEventsByUri('/foo_bar');

        $this->assertCount(1, $events);
        $this->assertSame('/foo_bar', $events->all()[0]->uri);
    }

    public function testGetEventsByAggregateId(): void
    {
        $this->store->append(Event::create('/orders/123', 'POST', [], null));
        $this->store->append(Event::create('/orders/123/items', 'POST', [], null));
        $this->store->append(Event::create('/orders/456', 'POST', [], null));

        $events = $this->store->getEventsByAggregateId('orders', '123');

        $this->assertCount(2, $events);
    }

    public function testGetEventsByAggregateIdRespectsIdBoundary(): void
    {
        $this->store->append(Event::create('/orders/123', 'POST', [], null));
        $this->store->append(Event::create('/orders/1234', 'POST', [], null));
        $this->store->append(Event::create('/orders/123/items', 'POST', [], null));
        $this->store->append(Event::create('/orders/123?reason=cancel', 'POST', [], null));

        $events = $this->store->getEventsByAggregateId('orders', '123');

        $uris = array_map(static fn (Event $e): string => $e->uri, $events->all());
        sort($uris);
        $this->assertSame(
            ['/orders/123', '/orders/123/items', '/orders/123?reason=cancel'],
            $uris,
        );
    }

    public function testAggregateTypeWithSlashRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->store->getEventsByAggregateId('orders/sub', '123');
    }

    public function testAggregateIdWithSlashRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->store->getEventsByAggregateId('orders', '123/items');
    }

    public function testInvalidTableNameRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new EventStore($this->pdo, 'event_store; DROP TABLE');
    }

    public function testCustomTableNameAccepted(): void
    {
        $this->pdo->exec(
            <<<'SQL'
            CREATE TABLE custom_events (
                id TEXT PRIMARY KEY,
                timestamp TEXT NOT NULL,
                uri TEXT NOT NULL,
                method TEXT NOT NULL,
                params TEXT,
                result TEXT
            )
        SQL
        );

        $store = new EventStore($this->pdo, 'custom_events');
        $store->append(Event::create('/x', 'POST', [], null));

        $this->assertCount(1, $store->getEvents());
    }

    private function insertWithTimestamp(string $id, string $uri, string $timestamp): void
    {
        $this->pdo->perform(
            'INSERT INTO event_store (id, timestamp, uri, method, params, result) VALUES (:id, :timestamp, :uri, :method, :params, :result)',
            [
                'id' => $id,
                'timestamp' => $timestamp,
                'uri' => $uri,
                'method' => 'POST',
                'params' => '[]',
                'result' => 'null',
            ],
        );
    }
}
