<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function testEmptyCollection(): void
    {
        $events = new Events();

        $this->assertCount(0, $events);
        $this->assertSame([], $events->all());
    }

    public function testAddIsImmutable(): void
    {
        $events = new Events();
        $newEvents = $events->add(Event::create('/test', 'POST', [], null));

        $this->assertCount(0, $events);
        $this->assertCount(1, $newEvents);
    }

    public function testFromJsonRoundTrip(): void
    {
        $events = new Events(
            [
            Event::create('/users/1', 'POST', [], null),
            Event::create('/users/2', 'POST', [], null),
            ]
        );

        $roundTripped = Events::fromJson($events->toJson());

        $this->assertCount(2, $roundTripped);
    }

    public function testFromJsonInvalidThrows(): void
    {
        $this->expectException(JsonException::class);
        Events::fromJson('{not json');
    }

    public function testToJsonProducesArray(): void
    {
        $events = new Events([Event::create('/test', 'POST', ['key' => 'value'], 'result')]);
        $decoded = json_decode($events->toJson(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('/test', $decoded[0]['uri']);
    }

    public function testFilterByUri(): void
    {
        $events = new Events(
            [
            Event::create('/users/1', 'POST', [], null),
            Event::create('/orders/1', 'POST', [], null),
            Event::create('/users/2', 'PUT', [], null),
            ]
        );

        $this->assertCount(2, $events->filterByUri('/users/*'));
    }

    public function testFilterByMethod(): void
    {
        $events = new Events(
            [
            Event::create('/a', 'POST', [], null),
            Event::create('/b', 'PUT', [], null),
            Event::create('/c', 'POST', [], null),
            ]
        );

        $this->assertCount(2, $events->filterByMethod('POST'));
    }

    public function testSinceFiltersByTimestamp(): void
    {
        $events = new Events(
            [
            Event::fromArray(['id' => '1', 'timestamp' => '2025-01-01T00:00:00+00:00', 'uri' => '/a', 'method' => 'POST']),
            Event::fromArray(['id' => '2', 'timestamp' => '2025-06-01T00:00:00+00:00', 'uri' => '/b', 'method' => 'POST']),
            Event::fromArray(['id' => '3', 'timestamp' => '2025-12-01T00:00:00+00:00', 'uri' => '/c', 'method' => 'POST']),
            ]
        );

        $filtered = $events->since(new DateTimeImmutable('2025-05-01'));

        $this->assertCount(2, $filtered);
    }

    public function testReplayPreservesOrder(): void
    {
        $events = new Events(
            [
            Event::create('/a', 'POST', [], null),
            Event::create('/b', 'PUT', [], null),
            ]
        );

        $uris = [];
        $events->replay(
            function (Event $e) use (&$uris): void {
                $uris[] = $e->uri;
            }
        );

        $this->assertSame(['/a', '/b'], $uris);
    }

    public function testIterable(): void
    {
        $events = new Events(
            [
            Event::create('/a', 'POST', [], null),
            Event::create('/b', 'PUT', [], null),
            ]
        );

        $uris = [];
        foreach ($events as $event) {
            $uris[] = $event->uri;
        }

        $this->assertSame(['/a', '/b'], $uris);
    }
}
