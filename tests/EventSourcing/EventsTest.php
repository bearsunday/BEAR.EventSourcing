<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EventsTest extends TestCase
{
    public function testEmptyCollection(): void
    {
        $events = new Events();

        $this->assertCount(0, $events);
        $this->assertSame([], $events->all());
    }

    public function testAdd(): void
    {
        $events = new Events();
        $event = Event::create('/test', 'POST', [], null);

        $newEvents = $events->add($event);

        $this->assertCount(0, $events);
        $this->assertCount(1, $newEvents);
    }

    public function testFromJson(): void
    {
        $json = json_encode([
            [
                'id' => 'uuid-1',
                'timestamp' => '2025-01-01 10:00:00.000000',
                'uri' => '/users/1',
                'method' => 'POST',
                'params' => [],
                'result' => null,
            ],
            [
                'id' => 'uuid-2',
                'timestamp' => '2025-01-01 11:00:00.000000',
                'uri' => '/users/2',
                'method' => 'POST',
                'params' => [],
                'result' => null,
            ],
        ]);

        $events = Events::fromJson($json);

        $this->assertCount(2, $events);
    }

    public function testToJson(): void
    {
        $event = Event::create('/test', 'POST', ['key' => 'value'], 'result');
        $events = new Events([$event]);

        $json = $events->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('/test', $decoded[0]['uri']);
    }

    public function testFilterByUri(): void
    {
        $events = new Events([
            Event::create('/users/1', 'POST', [], null),
            Event::create('/orders/1', 'POST', [], null),
            Event::create('/users/2', 'PUT', [], null),
        ]);

        $filtered = $events->filterByUri('/users/*');

        $this->assertCount(2, $filtered);
    }

    public function testFilterByMethod(): void
    {
        $events = new Events([
            Event::create('/a', 'POST', [], null),
            Event::create('/b', 'PUT', [], null),
            Event::create('/c', 'POST', [], null),
        ]);

        $filtered = $events->filterByMethod('POST');

        $this->assertCount(2, $filtered);
    }

    public function testReplay(): void
    {
        $events = new Events([
            Event::create('/a', 'POST', [], null),
            Event::create('/b', 'PUT', [], null),
        ]);

        $replayed = [];
        $events->replay(function (Event $e) use (&$replayed) {
            $replayed[] = $e->uri;
        });

        $this->assertSame(['/a', '/b'], $replayed);
    }

    public function testIterable(): void
    {
        $events = new Events([
            Event::create('/a', 'POST', [], null),
            Event::create('/b', 'PUT', [], null),
        ]);

        $uris = [];
        foreach ($events as $event) {
            $uris[] = $event->uri;
        }

        $this->assertSame(['/a', '/b'], $uris);
    }
}
