<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use DateTimeImmutable;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use BEAR\EventSourcing\Tests\Fixture\ResourceRequestContext;
use BEAR\EventSourcing\Tests\Fixture\ResourceResponseContext;
use stdClass;

use function array_map;

final class EventsFromSemanticLogTest extends TestCase
{
    #[DataProvider('recordedMethodsProvider')]
    public function testExtractsStateChangingMethods(string $method): void
    {
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext(
            uri: 'app://self/users',
            method: $method,
            query: ['name' => 'Ada'],
            timestamp: '2026-06-10T12:34:56.123456+00:00',
        ));
        $logger->close(new ResourceResponseContext(201, ['id' => 1]), $openId);

        $events = Events::fromSemanticLog($logger->flush()->toArray());

        $this->assertCount(1, $events);
        $event = $events->all()[0];
        $this->assertSame('app://self/users', $event->uri);
        $this->assertSame($method, $event->method);
        $this->assertSame(['name' => 'Ada'], $event->params);
        $this->assertSame(['id' => 1], $event->result);
        $this->assertEquals(new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'), $event->timestamp);
    }

    /** @return list<array{0: string}> */
    public static function recordedMethodsProvider(): array
    {
        return [['POST'], ['PUT'], ['PATCH'], ['DELETE']];
    }

    public function testIgnoresGetAndFailedResponses(): void
    {
        $logger = new SemanticLogger();
        $getId = $logger->open(new ResourceRequestContext('app://self/users/1', 'GET'));
        $logger->close(new ResourceResponseContext(200, ['id' => 1]), $getId);
        $postId = $logger->open(new ResourceRequestContext('app://self/users', 'POST'));
        $logger->close(new ResourceResponseContext(422, ['error' => 'invalid']), $postId);

        $events = Events::fromSemanticLog($logger->flush()->toArray());

        $this->assertCount(0, $events);
    }

    public function testExtractsNestedEventsInOpenOrder(): void
    {
        $logger = new SemanticLogger();
        $outerId = $logger->open(new ResourceRequestContext('app://self/orders', 'POST'));
        $innerId = $logger->open(new ResourceRequestContext('app://self/inventory/1', 'PUT'));
        $logger->close(new ResourceResponseContext(200, ['reserved' => true]), $innerId);
        $logger->close(new ResourceResponseContext(201, ['id' => 1]), $outerId);

        $events = Events::fromSemanticLog($logger->flush()->toArray());

        $this->assertSame(
            ['app://self/orders', 'app://self/inventory/1'],
            array_map(static fn (Event $event): string => $event->uri, $events->all()),
        );
    }

    public function testMalformedEntriesAreIgnored(): void
    {
        $events = Events::fromSemanticLog([
            'open' => [
                'not-an-entry',
                ['context' => ['uri' => 'app://self/users', 'method' => 'POST']],
                [
                    'context' => ['uri' => new stdClass(), 'method' => 'POST'],
                    'close' => ['context' => ['body' => null]],
                ],
                [
                    'context' => ['uri' => 'app://self/users', 'method' => 'POST', 'query' => 'invalid'],
                    'close' => ['context' => ['body' => ['ok' => true]]],
                    'open' => 'not-children',
                ],
            ],
        ]);

        $this->assertCount(1, $events);
        $event = $events->all()[0];
        $this->assertSame('app://self/users', $event->uri);
        $this->assertSame([], $event->params);
        $this->assertSame(['ok' => true], $event->result);
    }

    public function testMissingTimestampFallsBackToExtractionTime(): void
    {
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext('app://self/users', 'POST'));
        $logger->close(new ResourceResponseContext(201, ['id' => 1]), $openId);
        $before = new DateTimeImmutable('-1 second');

        $events = Events::fromSemanticLog($logger->flush()->toArray());
        $after = new DateTimeImmutable('+1 second');

        $timestamp = $events->all()[0]->timestamp;
        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }
}
