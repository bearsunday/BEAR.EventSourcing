<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use PHPUnit\Framework\TestCase;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class EventsTest extends TestCase
{
    private function createEvent(string $id, string $method = 'POST'): Event
    {
        return new Event(
            id: $id,
            timestamp: '2025-01-01T00:00:00+00:00',
            uri: 'app://self/user',
            method: $method,
            params: [],
            result: null,
        );
    }

    public function testCount(): void
    {
        $events = new Events([
            $this->createEvent('1'),
            $this->createEvent('2'),
        ]);

        $this->assertCount(2, $events);
    }

    public function testIterator(): void
    {
        $event1 = $this->createEvent('1');
        $event2 = $this->createEvent('2');
        $events = new Events([$event1, $event2]);

        $collected = [];
        foreach ($events as $event) {
            $collected[] = $event;
        }

        $this->assertSame([$event1, $event2], $collected);
    }

    public function testPlay(): void
    {
        $events = new Events([
            $this->createEvent('1'),
            $this->createEvent('2'),
        ]);

        $played = [];
        $events->play(static function (Event $event) use (&$played): void {
            $played[] = $event->id;
        });

        $this->assertSame(['1', '2'], $played);
    }

    public function testFilter(): void
    {
        $events = new Events([
            $this->createEvent('1', 'POST'),
            $this->createEvent('2', 'PUT'),
            $this->createEvent('3', 'POST'),
        ]);

        $filtered = $events->filter(static fn (Event $e): bool => $e->method === 'POST');

        $this->assertCount(2, $filtered);
    }

    public function testToJson(): void
    {
        $events = new Events([
            $this->createEvent('1'),
        ]);

        $json = $events->toJson();
        /** @var list<array<string, mixed>> $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $decoded);
        $this->assertSame('1', $decoded[0]['id']);
    }

    public function testFromJson(): void
    {
        $json = '[{"id":"1","timestamp":"2025-01-01T00:00:00+00:00","uri":"app://self/user","method":"POST","params":[],"result":null}]';

        $events = Events::fromJson($json);

        $this->assertCount(1, $events);
    }

    public function testAdd(): void
    {
        $events = new Events([]);
        $event = $this->createEvent('1');

        $newEvents = $events->add($event);

        $this->assertCount(0, $events);
        $this->assertCount(1, $newEvents);
    }

    public function testToArray(): void
    {
        $event = $this->createEvent('1');
        $events = new Events([$event]);

        $array = $events->toArray();

        $this->assertSame([$event], $array);
    }
}
