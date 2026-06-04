<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;
use BEAR\EventSourcing\Query\EventStoreQueryInterface;
use DateTimeImmutable;
use JsonException;
use PDO;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\MediaQuerySqlModule;
use UnexpectedValueException;

class SqlEventStoreTest extends TestCase
{
    private const QUERY_DIR = __DIR__ . '/../../src/Query';
    private const SQL_DIR = __DIR__ . '/../../sql';

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

    public function testGetEventsSince(): void
    {
        [$eventStore, $query] = $this->newEventStore();
        $this->insertStoredEvent($query, 'event-1', '2025-01-01 00:00:00.000000', '/users/1');
        $this->insertStoredEvent($query, 'event-2', '2025-01-02 00:00:00.000000', '/users/2');
        $this->insertStoredEvent($query, 'event-3', '2025-01-03 00:00:00.000000', '/users/3');

        $events = $eventStore->getEventsSince(new DateTimeImmutable('2025-01-02 00:00:00.000000'));

        $this->assertSame(['/users/2', '/users/3'], $this->uris($events));
    }

    public function testGetEventsByUriUsesGlobPattern(): void
    {
        [$eventStore, $query] = $this->newEventStore();
        $this->insertStoredEvent($query, 'event-1', '2025-01-01 00:00:00.000000', '/users/1');
        $this->insertStoredEvent($query, 'event-2', '2025-01-02 00:00:00.000000', '/users/2');
        $this->insertStoredEvent($query, 'event-3', '2025-01-03 00:00:00.000000', '/orders/1');

        $events = $eventStore->getEventsByUri('/users/*');

        $this->assertSame(['/users/1', '/users/2'], $this->uris($events));
    }

    public function testGetEventsByUriEscapesSqlLikeWildcards(): void
    {
        [$eventStore, $query] = $this->newEventStore();
        $this->insertStoredEvent($query, 'event-1', '2025-01-01 00:00:00.000000', '/reports/100%');
        $this->insertStoredEvent($query, 'event-2', '2025-01-02 00:00:00.000000', '/reports/100x');

        $events = $eventStore->getEventsByUri('/reports/100%');

        $this->assertSame(['/reports/100%'], $this->uris($events));
    }

    public function testGetEventsByAggregateIdDoesNotMatchPrefixCollision(): void
    {
        [$eventStore, $query] = $this->newEventStore();
        $this->insertStoredEvent($query, 'event-1', '2025-01-01 00:00:00.000000', '/orders/123');
        $this->insertStoredEvent($query, 'event-2', '2025-01-02 00:00:00.000000', '/orders/123/items/1');
        $this->insertStoredEvent($query, 'event-3', '2025-01-03 00:00:00.000000', '/orders/1234');

        $events = $eventStore->getEventsByAggregateId('orders', '123');

        $this->assertSame(['/orders/123', '/orders/123/items/1'], $this->uris($events));
    }

    public function testGetEventsByAggregateIdEscapesSqlLikeWildcards(): void
    {
        [$eventStore, $query] = $this->newEventStore();
        $this->insertStoredEvent($query, 'event-1', '2025-01-01 00:00:00.000000', '/orders/12_3');
        $this->insertStoredEvent($query, 'event-2', '2025-01-02 00:00:00.000000', '/orders/12_3/items/1');
        $this->insertStoredEvent($query, 'event-3', '2025-01-03 00:00:00.000000', '/orders/12a3');

        $events = $eventStore->getEventsByAggregateId('orders', '12_3');

        $this->assertSame(['/orders/12_3', '/orders/12_3/items/1'], $this->uris($events));
    }

    public function testInvalidStoredJsonThrows(): void
    {
        [$eventStore, $query] = $this->newEventStore();

        $this->insertStoredEvent($query, 'invalid-json', '2025-01-01 00:00:00.000000', '/users/1', params: '{');

        $this->expectException(JsonException::class);

        $eventStore->getEvents();
    }

    public function testCreateTableRejectsUnsupportedDriver(): void
    {
        $pdo = $this->getMockBuilder(ExtendedPdo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();
        $pdo->expects($this->once())
            ->method('getAttribute')
            ->with(PDO::ATTR_DRIVER_NAME)
            ->willReturn('pgsql');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported PDO driver: pgsql');

        (new SqlEventStore($pdo, $this->createMock(EventStoreQueryInterface::class)))->createTable();
    }

    /** @return array{SqlEventStore, EventStoreQueryInterface} */
    private function newEventStore(): array
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        $injector = new Injector(new class ($pdo, self::QUERY_DIR, self::SQL_DIR) extends AbstractModule {
            public function __construct(
                private readonly ExtendedPdo $pdo,
                private readonly string $queryDir,
                private readonly string $sqlDir,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(ExtendedPdoInterface::class)->toInstance($this->pdo);
                $this->install(new AuraSqlPagerModule());
                $this->install(new MediaQuerySqlModule($this->queryDir, $this->sqlDir));
            }
        });
        $eventStore = $injector->getInstance(SqlEventStore::class);
        $eventStore->createTable();

        return [$eventStore, $injector->getInstance(EventStoreQueryInterface::class)];
    }

    private function insertStoredEvent(
        EventStoreQueryInterface $query,
        string $id,
        string $timestamp,
        string $uri,
        string $method = 'POST',
        string $params = '[]',
        string $result = 'null',
    ): void {
        $query->append($id, $timestamp, $uri, $method, $params, $result);
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
