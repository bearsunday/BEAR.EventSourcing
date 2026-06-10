<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testCreateNormalizesMethodAndSerializes(): void
    {
        $timestamp = new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00');
        $event = Event::create(
            uri: 'app://self/users',
            method: 'post',
            params: ['name' => 'Ada'],
            result: ['id' => 1],
            timestamp: $timestamp,
        );

        $this->assertSame('POST', $event->method);
        $this->assertSame([
            'uri' => 'app://self/users',
            'method' => 'POST',
            'params' => ['name' => 'Ada'],
            'result' => ['id' => 1],
            'timestamp' => '2026-06-10T12:34:56.123456+00:00',
        ], $event->toArray());
    }

    public function testFromArrayRestoresTimestamp(): void
    {
        $event = Event::fromArray([
            'uri' => 'app://self/users/1',
            'method' => 'PUT',
            'params' => ['name' => 'Grace'],
            'result' => ['ok' => true],
            'timestamp' => '2026-06-10T12:34:56.123456+00:00',
        ]);

        $this->assertSame('app://self/users/1', $event->uri);
        $this->assertSame('PUT', $event->method);
        $this->assertEquals(new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00'), $event->timestamp);
    }
}
