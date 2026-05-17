<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testCreate(): void
    {
        $event = Event::create('/users/1', 'POST', ['name' => 'John'], ['id' => 1]);

        $this->assertNotEmpty($event->id);
        $this->assertSame('/users/1', $event->uri);
        $this->assertSame('POST', $event->method);
        $this->assertSame(['name' => 'John'], $event->params);
        $this->assertSame(['id' => 1], $event->result);
    }

    public function testFromArray(): void
    {
        $event = Event::fromArray(
            [
            'id' => 'test-uuid',
            'timestamp' => '2025-01-01T12:00:00+00:00',
            'uri' => '/orders/1',
            'method' => 'PUT',
            'params' => ['status' => 'shipped'],
            'result' => ['success' => true],
            ]
        );

        $this->assertSame('test-uuid', $event->id);
        $this->assertSame('/orders/1', $event->uri);
        $this->assertSame('PUT', $event->method);
        $this->assertSame(['status' => 'shipped'], $event->params);
    }

    public function testFromArrayMissingKeyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Event::fromArray(['id' => 'x', 'uri' => '/x', 'method' => 'POST']);
    }

    public function testToArrayProducesIso8601Timestamp(): void
    {
        $event = Event::create('/products/1', 'DELETE', [], null);
        $array = $event->toArray();

        $this->assertSame('/products/1', $array['uri']);
        $this->assertSame('DELETE', $array['method']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}[+-]\d{2}:\d{2}$/', $array['timestamp']);
    }

    public function testJsonSerialize(): void
    {
        $event = Event::create('/test', 'POST', ['key' => 'value'], 'result');
        $decoded = json_decode((string) json_encode($event), true);

        $this->assertSame('/test', $decoded['uri']);
    }

    public function testRoundTripFromArrayToArray(): void
    {
        $event = Event::create('/users/1', 'POST', ['x' => 1], ['ok' => true]);
        $copy = Event::fromArray($event->toArray());

        $this->assertEquals($event, $copy);
    }
}
