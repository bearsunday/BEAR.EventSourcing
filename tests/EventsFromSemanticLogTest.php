<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Exception\InvalidRecordedMethod;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\ResourceResponseContext as BodyResponseContext;
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

    #[DataProvider('responseCodeProvider')]
    public function testClassifiesResponseCodes(mixed $code, int $expectedCount): void
    {
        $semanticLog = new LogJson(
            schemaUrl: 'https://example.com/semantic-log.schema.json',
            open: [new OpenCloseEntry(
                id: 'resource_request_1',
                type: 'resource_request',
                schemaUrl: 'https://example.com/resource-request.schema.json',
                context: [
                    'uri' => 'app://self/users',
                    'method' => 'POST',
                    'timestamp' => '2026-06-10T12:34:56.123456+00:00',
                ],
            )],
            close: [new EventEntry(
                id: 'resource_response_1',
                type: 'resource_response',
                schemaUrl: 'https://example.com/resource-response.schema.json',
                context: ['code' => $code, 'body' => ['id' => 1]],
                openId: 'resource_request_1',
            )],
        );

        $events = (new SemanticLogExtractor())->extract($semanticLog);

        $this->assertCount($expectedCount, $events);
    }

    /** @return array<string, array{0: mixed, 1: int}> */
    public static function responseCodeProvider(): array
    {
        return [
            'explicit null keeps the event' => [null, 1],
            'int success' => [201, 1],
            'int boundary 399' => [399, 1],
            'int boundary 400' => [400, 0],
            'int client error' => [409, 0],
            'numeric string success' => ['200', 1],
            'numeric string failure' => ['500', 0],
            'float is uninterpretable' => [500.0, 0],
            'bool is uninterpretable' => [true, 0],
            'non-numeric string is uninterpretable' => ['error', 0],
        ];
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

    public function testBodyReferenceIsNotExtractedIntoResult(): void
    {
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext('app://self/users/1', 'POST'));
        $logger->close(new BodyResponseContext(200, 'file://var/es/bodies/000001.json'), $openId);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        // body_ref stays in the Semantic Log for inspection; it must not leak into the domain event.
        $event = iterator_to_array($events)[0];
        $this->assertSame('app://self/users/1', $event->uri);
        $this->assertNull($event->result);
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

    public function testNestedRequestsAreNotExtracted(): void
    {
        $logger = new SemanticLogger();
        $outerId = $logger->open(new ResourceRequestContext('app://self/orders', 'POST'));
        $innerId = $logger->open(new ResourceRequestContext('app://self/inventory/1', 'PUT'));
        $logger->close(new ResourceResponseContext(200, ['reserved' => true]), $innerId);
        $logger->close(new ResourceResponseContext(201, ['id' => 1]), $outerId);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $this->assertSame(
            ['app://self/orders'],
            array_map(static fn (Event $event): string => $event->uri, iterator_to_array($events)),
        );
    }

    public function testNestedWriteUnderAnUnrecordedRootIsNotExtracted(): void
    {
        $logger = new SemanticLogger();
        $outerId = $logger->open(new ResourceRequestContext('app://self/orders/1', 'GET'));
        $innerId = $logger->open(new ResourceRequestContext('app://self/inventory/1', 'PUT'));
        $logger->close(new ResourceResponseContext(200, ['reserved' => true]), $innerId);
        $logger->close(new ResourceResponseContext(200, ['id' => 1]), $outerId);

        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $this->assertCount(0, $events);
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

    public function testNonResourceTypeWithResourceShapedContextIsIgnored(): void
    {
        // An http_client entry carries method/uri/timestamp too; only the
        // resource_request type may become an event.
        $semanticLog = new LogJson(
            schemaUrl: 'https://example.com/semantic-log.schema.json',
            open: [new OpenCloseEntry(
                id: 'http_client_1',
                type: 'http_client',
                schemaUrl: 'https://example.com/http-client.schema.json',
                context: [
                    'method' => 'POST',
                    'uri' => 'https://api.example.com/charges',
                    'timestamp' => '2026-06-10T12:34:56.123456+00:00',
                ],
            )],
            close: [new EventEntry(
                id: 'http_client_response_1',
                type: 'http_client_response',
                schemaUrl: 'https://example.com/http-client-response.schema.json',
                context: ['code' => 201, 'body' => ['ok' => true]],
                openId: 'http_client_1',
            )],
        );

        $events = (new SemanticLogExtractor())->extract($semanticLog);

        $this->assertCount(0, $events);
    }

    #[DataProvider('nonCanonicalTimestampProvider')]
    public function testEntriesWithoutCanonicalObservedTimestampAreNotExtracted(string $timestamp): void
    {
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext('app://self/users', 'POST', timestamp: $timestamp));
        $logger->close(new ResourceResponseContext(201, ['id' => 1]), $openId);

        // No fallback clock and no lenient parsing: an event is only as real as its observation.
        $events = (new SemanticLogExtractor())->extract($logger->flush());

        $this->assertCount(0, $events);
    }

    /** @return array<string, array{0: string}> */
    public static function nonCanonicalTimestampProvider(): array
    {
        return [
            'missing' => [''],
            'relative changes per extraction' => ['now'],
            'offset-less depends on the environment' => ['2026-06-10T12:34:56.123456'],
        ];
    }

    public function testExtractionIsDeterministic(): void
    {
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext(
            uri: 'app://self/users',
            method: 'POST',
            query: ['name' => 'Ada'],
        ));
        $logger->close(new ResourceResponseContext(201, ['id' => 1]), $openId);
        $semanticLog = $logger->flush();

        $first = iterator_to_array((new SemanticLogExtractor())->extract($semanticLog))[0];
        $second = iterator_to_array((new SemanticLogExtractor())->extract($semanticLog))[0];

        $this->assertSame($first->id, $second->id);
        $this->assertEquals($first->timestamp, $second->timestamp);
    }
}
