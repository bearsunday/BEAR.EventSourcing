<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\EventsInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function testCollectionIsIterableAndCountable(): void
    {
        $timestamp = new DateTimeImmutable('2026-06-10T12:34:56.123456+00:00');
        $post = new Event('app://self/users', 'POST', $timestamp, ['name' => 'Ada']);
        $put = new Event('app://self/users/1', 'PUT', $timestamp, ['name' => 'Grace']);

        $events = new Events([$post, $put]);

        $this->assertInstanceOf(EventsInterface::class, $events);
        $this->assertCount(2, $events);
        $this->assertSame([$post, $put], iterator_to_array($events));
        $this->assertCount(0, new Events());
    }
}
