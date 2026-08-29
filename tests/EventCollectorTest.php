<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\EventCollector;
use BEAR\EventSourcing\Resource\ResourceRequestContext;
use BEAR\EventSourcing\Resource\ResourceResponseContext;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\EventSourcing\Store\InMemoryEventStore;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

final class EventCollectorTest extends TestCase
{
    public function testCollectsExtractsAndAppends(): void
    {
        $logger = self::loggerWithRecordedWrite();
        $store = new InMemoryEventStore();
        $collect = new EventCollector($logger, new SemanticLogExtractor(), $store);

        $events = $collect();

        $this->assertCount(1, $events);
        $this->assertCount(1, $store->all());
        $this->assertSame('app://self/users', iterator_to_array($store->all(), false)[0]->uri);
    }

    public function testEmptySessionYieldsNoEventsAndNoAppend(): void
    {
        $logger = new SemanticLogger();
        $store = new InMemoryEventStore();
        $collect = new EventCollector($logger, new SemanticLogExtractor(), $store);

        // A request that recorded nothing (e.g. only GET reads under the default
        // RecordedMethods) flushes to an empty log: no events, nothing appended.
        $this->assertCount(0, $collect());
        $this->assertCount(0, $store->all());
    }

    public function testCollectsWithoutStore(): void
    {
        $collect = new EventCollector(self::loggerWithRecordedWrite(), new SemanticLogExtractor());

        $this->assertCount(1, $collect());
    }

    public function testRetriedInvocationDoesNotDuplicateFacts(): void
    {
        $logger = self::loggerWithRecordedWrite();
        $store = new InMemoryEventStore();
        $collect = new EventCollector($logger, new SemanticLogExtractor(), $store);

        $collect();
        // The next request boundary with an empty session appends nothing.
        $collect();

        $this->assertCount(1, $store->all());
    }

    private static function loggerWithRecordedWrite(): SemanticLogger
    {
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext(
            uri: 'app://self/users',
            method: 'POST',
            params: ['id' => 'koriym'],
            timestamp: '2026-06-10T12:34:56.123456+00:00',
        ));
        $logger->close(new ResourceResponseContext(201), $openId);

        return $logger;
    }
}
