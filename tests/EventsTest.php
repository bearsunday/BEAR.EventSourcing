<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\EventsInterface;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function testCollectionIsIterableAndCountable(): void
    {
        $post = Event::create('app://self/users', 'POST', ['name' => 'Ada']);
        $put = Event::create('app://self/users/1', 'PUT', ['name' => 'Grace']);

        $events = new Events([$post, $put]);

        $this->assertInstanceOf(EventsInterface::class, $events);
        $this->assertCount(2, $events);
        $this->assertSame([$post, $put], iterator_to_array($events));
        $this->assertCount(0, new Events());
    }
}
