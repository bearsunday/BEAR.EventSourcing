<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Exception\EventStoreException;
use BEAR\EventSourcing\Store\MediaQueryEventStore;
use BEAR\EventSourcing\Tests\Fixture\FakeEventStoreQuery;
use BEAR\EventSourcing\Tests\Fixture\MediaQueryEventStoreAppModule;
use Composer\InstalledVersions;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function file_get_contents;
use function file_put_contents;
use function iterator_to_array;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function tempnam;
use function uniqid;
use function unlink;
use function version_compare;

use const PHP_VERSION_ID;

#[RequiresPhpExtension('pdo_sqlite')]
final class MediaQueryEventStoreTest extends TestCase
{
    private string|null $databaseFile = null;
    private string|null $staleSqlDir = null;

    protected function setUp(): void
    {
        // aura/sql < 6 declares PDO::connect() non-static, a fatal on PHP >= 8.4.
        $auraSqlVersion = (string) InstalledVersions::getVersion('aura/sql');
        if (PHP_VERSION_ID >= 80400 && version_compare($auraSqlVersion, '6.0.0', '<')) {
            $this->markTestSkipped('aura/sql < 6 cannot load on PHP >= 8.4; update dependencies to run this test.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->databaseFile !== null) {
            @unlink($this->databaseFile);
        }

        if ($this->staleSqlDir === null) {
            return;
        }

        // The two files staleSqlDir() writes, by name: nothing else is ever put there.
        @unlink($this->staleSqlDir . '/event_store_list.sql');
        @unlink($this->staleSqlDir . '/event_store_append.sql');
        @rmdir($this->staleSqlDir);
    }

    public function testAppendStoresAndRestoresEventsInInsertionOrder(): void
    {
        $store = $this->store();
        $first = self::event('app://self/users', 'POST', ['name' => 'Ada'], ['id' => 1]);
        $second = self::event('app://self/users/1', 'PATCH', ['name' => 'Grace'], ['ok' => true]);

        $store->append($first);
        $store->append($second);

        $stored = iterator_to_array($store->all());
        $this->assertCount(2, $stored);
        $this->assertEventEquals($first, $stored[0]);
        $this->assertEventEquals($second, $stored[1]);
    }

    public function testAppendAllStoresEventCollection(): void
    {
        $store = $this->store();
        $store->appendAll(new Events([
            self::event('app://self/orders', 'POST', ['sku' => 'A'], ['id' => 1]),
            self::event('app://self/orders/1', 'DELETE', [], null),
        ]));

        $this->assertCount(2, $store->all());
    }

    public function testCanBeUsedThroughEventStoreInterface(): void
    {
        $store = $this->store();

        $this->assertInstanceOf(EventStoreInterface::class, $store);
    }

    public function testAppendIsIdempotentPerEventId(): void
    {
        $store = $this->store();
        $events = new Events([self::event('app://self/users', 'POST', ['name' => 'Ada'], ['id' => 1])]);

        // A retried batch must not duplicate facts in the source of truth.
        $store->appendAll($events);
        $store->appendAll($events);

        $this->assertCount(1, $store->all());
    }

    public function testTimestampIsStoredInUtcAndIdentityIsPreserved(): void
    {
        $store = $this->store();
        $event = new Event(
            uri: 'app://self/users',
            method: 'POST',
            timestamp: new DateTimeImmutable('2026-06-10T21:34:56.123456+09:00'),
            params: ['name' => 'Ada'],
        );

        $store->append($event);

        $restored = iterator_to_array($store->all())[0];
        $this->assertSame($event->id, $restored->id);
        $this->assertEquals($event->timestamp, $restored->timestamp);
        $this->assertSame('+00:00', $restored->timestamp->format('P'));
    }

    public function testReplayableFlagRoundTripsThroughTheDatabase(): void
    {
        // The log is transient; the store is what a replay engine reads, so the flag a
        // filter set at record time has to survive the row and come back as the same bool.
        $store = $this->store();
        $withheld = new Event(
            uri: 'app://self/admin/login',
            method: 'POST',
            timestamp: new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'),
            params: ['loginId' => 'admin', 'password' => '[FILTERED]'],
            replayable: false,
        );
        $intact = self::event('app://self/products', 'POST', ['name' => 'Ada'], ['id' => 1]);

        $store->append($withheld);
        $store->append($intact);

        $stored = iterator_to_array($store->all());
        $this->assertFalse($stored[0]->replayable);
        $this->assertSame($withheld->id, $stored[0]->id);
        $this->assertTrue($stored[1]->replayable);
    }

    public function testAppendWrapsAQueryFailureInEventStoreException(): void
    {
        $store = $this->store();
        (new PDO('sqlite:' . (string) $this->databaseFile))->exec('DROP TABLE event_store');

        // A database-level failure is remapped to the EventStore contract, not leaked as a Ray.MediaQuery exception.
        $this->expectException(EventStoreException::class);
        $store->append(self::event('app://self/users', 'POST', ['id' => 1], null));
    }

    public function testAppendRejectsNonStringParamKeys(): void
    {
        $store = $this->store();

        // Fail fast on append rather than letting a bad row poison a later all() for the whole store.
        $this->expectException(EventStoreException::class);
        /** @psalm-suppress InvalidArgument Intentionally violating the string-keyed contract at runtime. */
        $store->append(new Event(
            uri: 'app://self/users',
            method: 'POST',
            timestamp: new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'),
            params: ['x', 'y'],
            result: null,
        ));
    }

    public function testAStaleListSqlWithoutTheReplayableColumnIsAnErrorNotANonReplayableEvent(): void
    {
        // An application that copied sql/event_store/*.sql under 0.1.0 has a list.sql that does
        // not select the column. Reading the absence as `false` would hand a replay engine a
        // wrong verdict for every event behind nothing louder than an E_WARNING, so it has to
        // be the error it is — and the message has to say which file to re-copy.
        $store = new MediaQueryEventStore(new FakeEventStoreQuery([
            [
                'event_id' => 'e1',
                'uri' => 'app://self/users',
                'method' => 'POST',
                'params_json' => '{"name":"Ada"}',
                'result_json' => 'null',
                'recorded_at' => '2026-06-10T12:34:56.123456+00:00',
            ],
        ]));

        $this->expectException(EventStoreException::class);
        $this->expectExceptionMessageMatches('/replayable column.*sql\/event_store/s');
        iterator_to_array($store->all());
    }

    public function testAStaleAppendSqlSilentlyStoresANonReplayableEventAsReplayable(): void
    {
        // The other half of the 0.1.0 sqlDir migration, and the dangerous half: a stale
        // append.sql has no :replayable placeholder, Ray.MediaQuery binds only the placeholders
        // a file contains, so the INSERT succeeds and the column takes its DEFAULT 1. README
        // warns that this one is silent — no exception, no warning — because INSERT OR IGNORE
        // swallows a constraint violation as readily as a duplicate id. Pinned here so the
        // warning stops being true out loud rather than quietly.
        $sqlDir = $this->staleSqlDir();
        $store = $this->store($sqlDir);

        $store->append(new Event(
            uri: 'app://self/admin/login',
            method: 'POST',
            timestamp: new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'),
            params: ['loginId' => 'admin', 'password' => '[FILTERED]'],
            replayable: false,
        ));

        $restored = iterator_to_array($store->all());
        $this->assertCount(1, $restored);
        $this->assertTrue(
            $restored[0]->replayable,
            'the stale append.sql loses the verdict to DEFAULT 1 — silently, which is why the '
            . 'README tells applications to re-copy both SQL files together',
        );
    }

    /** A sqlDir whose append.sql predates the replayable column; list.sql is the current one. */
    private function staleSqlDir(): string
    {
        $dir = sys_get_temp_dir() . '/bear_es_stale_' . uniqid();
        mkdir($dir);
        $this->staleSqlDir = $dir;

        $list = file_get_contents(__DIR__ . '/../sql/event_store/event_store_list.sql');
        $this->assertIsString($list);
        file_put_contents($dir . '/event_store_list.sql', $list);
        file_put_contents($dir . '/event_store_append.sql', <<<'SQL'
        INSERT OR IGNORE INTO event_store (
            event_id,
            uri,
            method,
            params_json,
            result_json,
            recorded_at
        ) VALUES (
            :eventId,
            :uri,
            :method,
            :paramsJson,
            :resultJson,
            :timestamp
        )
        SQL);

        return $dir;
    }

    public function testAllWrapsAQueryFailureInEventStoreExceptionLikeAppendDoes(): void
    {
        // append() already promised this contract; all() called the query outside the guard, so
        // a stale sqlDir surfaced as a raw Ray.MediaQuery exception instead.
        $store = new MediaQueryEventStore(new FakeEventStoreQuery(null));

        $this->expectException(EventStoreException::class);
        $store->all();
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function event(string $uri, string $method, array $params, mixed $result): Event
    {
        return new Event(
            uri: $uri,
            method: $method,
            timestamp: new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'),
            params: $params,
            result: $result,
        );
    }

    private function store(string|null $sqlDir = null): EventStoreInterface
    {
        $databaseFile = tempnam(sys_get_temp_dir(), 'bear_es_');
        $this->assertIsString($databaseFile);
        $this->databaseFile = $databaseFile;

        $schema = file_get_contents(__DIR__ . '/../sql/event_store/schema.sql');
        $this->assertIsString($schema);
        (new PDO('sqlite:' . $this->databaseFile))->exec($schema);

        $injector = new Injector(new MediaQueryEventStoreAppModule($this->databaseFile, $sqlDir));
        $store = $injector->getInstance(EventStoreInterface::class);
        $this->assertInstanceOf(MediaQueryEventStore::class, $store);

        return $store;
    }

    private function assertEventEquals(Event $expected, Event $actual): void
    {
        $this->assertSame($expected->uri, $actual->uri);
        $this->assertSame($expected->method, $actual->method);
        $this->assertEquals($expected->timestamp, $actual->timestamp);
        $this->assertSame($expected->params, $actual->params);
        $this->assertSame($expected->result, $actual->result);
        $this->assertSame($expected->replayable, $actual->replayable);
    }
}
