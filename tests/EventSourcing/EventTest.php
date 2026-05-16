<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
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
        $data = [
            'id' => 'test-uuid',
            'timestamp' => '2025-01-01 12:00:00.000000',
            'uri' => '/orders/1',
            'method' => 'PUT',
            'params' => ['status' => 'shipped'],
            'result' => ['success' => true],
        ];

        $event = Event::fromArray($data);

        $this->assertSame('test-uuid', $event->id);
        $this->assertSame('/orders/1', $event->uri);
        $this->assertSame('PUT', $event->method);
        $this->assertSame(['status' => 'shipped'], $event->params);
    }

    public function testToArray(): void
    {
        $event = Event::create('/products/1', 'DELETE', [], null);
        $array = $event->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertSame('/products/1', $array['uri']);
        $this->assertSame('DELETE', $array['method']);
    }

    public function testJsonSerialize(): void
    {
        $event = Event::create('/test', 'POST', ['key' => 'value'], 'result');
        $json = json_encode($event);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertSame('/test', $decoded['uri']);
    }
}
