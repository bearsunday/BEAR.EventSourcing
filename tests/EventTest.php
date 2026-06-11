<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testConstructNormalizesMethod(): void
    {
        $timestamp = new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00');
        $event = new Event(
            uri: 'app://self/users',
            method: 'post',
            timestamp: $timestamp,
            params: ['name' => 'Ada'],
            result: ['id' => 1],
        );

        $this->assertSame('app://self/users', $event->uri);
        $this->assertSame('POST', $event->method);
        $this->assertSame($timestamp, $event->timestamp);
        $this->assertSame(['name' => 'Ada'], $event->params);
        $this->assertSame(['id' => 1], $event->result);
    }
}
