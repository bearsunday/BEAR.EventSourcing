<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Exception\InvalidRecordedMethod;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\ResourceResponseContext as ViewResponseContext;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\EventSourcing\SemanticLogExtractorInterface;
use DateTimeImmutable;
use Koriym\SemanticLogger\EventEntry;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\OpenCloseEntry;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use BEAR\EventSourcing\Tests\Fixture\ResourceRequestContext;
use BEAR\EventSourcing\Tests\Fixture\ResourceResponseContext;

use function array_map;

final class EventsFromSemanticLogTest extends TestCase
{
    public function testExtractorCanBeInjectedByInterface(): void
    {
        $extractor = new SemanticLogExtractor();

        $this->assertInstanceOf(SemanticLogExtractorInterface::class, $extractor);
    }

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

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $this->assertCount(1, $events);
        $event = iterator_to_array($events)[0];
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

    public function testIgnoresGetByDefaultAndFailedResponses(): void
    {
        $logger = new SemanticLogger();
        $getId = $logger->open(new ResourceRequestContext('app://self/users/1', 'GET'));
        $logger->close(new ResourceResponseContext(200, ['id' => 1]), $getId);
        $postId = $logger->open(new ResourceRequestContext('app://self/users', 'POST'));
        $logger->close(new ResourceResponseContext(422, ['error' => 'invalid']), $postId);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $this->assertCount(0, $events);
    }

    public function testCanRecordGetForDevelopment(): void
    {
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext('app://self/users/1', 'GET'));
        $logger->close(new ResourceResponseContext(200, ['id' => 1]), $openId);

        $extractor = new SemanticLogExtractor(new RecordedMethods(RecordedMethods::WITH_READS));

        $events = $extractor->extract($logger->flush());

        $this->assertCount(1, $events);
        $event = iterator_to_array($events)[0];
        $this->assertSame('GET', $event->method);
        $this->assertSame('app://self/users/1', $event->uri);
        $this->assertSame(['id' => 1], $event->result);
    }

    public function testExtractsViewReferenceAsResult(): void
    {
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext('app://self/users/1', 'POST'));
        $logger->close(new ViewResponseContext(200, 'file://var/es/views/000001.json'), $openId);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $event = iterator_to_array($events)[0];
        $this->assertSame(['view_ref' => 'file://var/es/views/000001.json'], $event->result);
    }

    public function testRejectsUnsupportedRecordedMethod(): void
    {
        $this->expectException(InvalidRecordedMethod::class);

        new RecordedMethods(['POST', 'OPTIONS']);
    }

    public function testRejectsNonStringRecordedMethod(): void
    {
        $this->expectException(InvalidRecordedMethod::class);

        new RecordedMethods(['POST', 1]);
    }

    public function testExtractsNestedEventsInOpenOrder(): void
    {
        $logger = new SemanticLogger();
        $outerId = $logger->open(new ResourceRequestContext('app://self/orders', 'POST'));
        $innerId = $logger->open(new ResourceRequestContext('app://self/inventory/1', 'PUT'));
        $logger->close(new ResourceResponseContext(200, ['reserved' => true]), $innerId);
        $logger->close(new ResourceResponseContext(201, ['id' => 1]), $outerId);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $this->assertSame(
            ['app://self/orders', 'app://self/inventory/1'],
            array_map(static fn (Event $event): string => $event->uri, iterator_to_array($events)),
        );
    }

    public function testNonResourceOperationsAreIgnored(): void
    {
        $semanticLog = new LogJson(
            schemaUrl: 'https://example.com/semantic-log.schema.json',
            open: [new OpenCloseEntry(
                id: 'operation_1',
                type: 'operation',
                schemaUrl: 'https://example.com/operation.schema.json',
                context: ['name' => 'not-a-resource-request'],
            )],
            close: [new EventEntry(
                id: 'operation_response_1',
                type: 'operation_response',
                schemaUrl: 'https://example.com/operation-response.schema.json',
                context: ['body' => ['ok' => true]],
                openId: 'operation_1',
            )],
        );

        $events = (new SemanticLogExtractor())->extract($semanticLog);

        $this->assertCount(0, $events);
    }

    public function testMissingTimestampFallsBackToExtractionTime(): void
    {
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext('app://self/users', 'POST'));
        $logger->close(new ResourceResponseContext(201, ['id' => 1]), $openId);
        $before = new DateTimeImmutable('-1 second');

        $events = (new SemanticLogExtractor())->extract($logger->flush());
        $after = new DateTimeImmutable('+1 second');

        $timestamp = iterator_to_array($events)[0]->timestamp;
        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }
}
