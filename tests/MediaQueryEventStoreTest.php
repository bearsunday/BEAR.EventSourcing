<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Store\MediaQueryEventStore;
use BEAR\EventSourcing\Tests\Fixture\MediaQueryEventStoreAppModule;
use Composer\InstalledVersions;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function file_get_contents;
use function iterator_to_array;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use function version_compare;

use const PHP_VERSION_ID;

#[RequiresPhpExtension('pdo_sqlite')]
final class MediaQueryEventStoreTest extends TestCase
{
    private string|null $databaseFile = null;

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

    private function store(): EventStoreInterface
    {
        $databaseFile = tempnam(sys_get_temp_dir(), 'bear_es_');
        $this->assertIsString($databaseFile);
        $this->databaseFile = $databaseFile;

        $schema = file_get_contents(__DIR__ . '/../sql/event_store/schema.sql');
        $this->assertIsString($schema);
        (new PDO('sqlite:' . $this->databaseFile))->exec($schema);

        $injector = new Injector(new MediaQueryEventStoreAppModule($this->databaseFile));
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
    }
}
