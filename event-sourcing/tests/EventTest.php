<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testConstructor(): void
    {
        $event = new Event(
            id: 'test-id-123',
            timestamp: '2025-01-01T00:00:00+00:00',
            uri: 'app://self/user',
            method: 'POST',
            params: ['name' => 'John'],
            result: ['id' => 1, 'name' => 'John'],
        );

        $this->assertSame('test-id-123', $event->id);
        $this->assertSame('2025-01-01T00:00:00+00:00', $event->timestamp);
        $this->assertSame('app://self/user', $event->uri);
        $this->assertSame('POST', $event->method);
        $this->assertSame(['name' => 'John'], $event->params);
        $this->assertSame(['id' => 1, 'name' => 'John'], $event->result);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'test-id-123',
            'timestamp' => '2025-01-01T00:00:00+00:00',
            'uri' => 'app://self/user',
            'method' => 'POST',
            'params' => ['name' => 'John'],
            'result' => ['id' => 1, 'name' => 'John'],
        ];

        $event = Event::fromArray($data);

        $this->assertSame('test-id-123', $event->id);
        $this->assertSame('app://self/user', $event->uri);
    }

    public function testToArray(): void
    {
        $event = new Event(
            id: 'test-id-123',
            timestamp: '2025-01-01T00:00:00+00:00',
            uri: 'app://self/user',
            method: 'POST',
            params: ['name' => 'John'],
            result: ['id' => 1],
        );

        $array = $event->toArray();

        $this->assertSame('test-id-123', $array['id']);
        $this->assertSame('POST', $array['method']);
        $this->assertSame('app://self/user', $array['uri']);
    }

    public function testJsonSerialize(): void
    {
        $event = new Event(
            id: 'test-id-123',
            timestamp: '2025-01-01T00:00:00+00:00',
            uri: 'app://self/user',
            method: 'POST',
            params: [],
            result: null,
        );

        $json = json_encode($event, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('test-id-123', $decoded['id']);
    }
}
