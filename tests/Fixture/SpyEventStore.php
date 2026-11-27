<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\EventsInterface;
use BEAR\EventSourcing\EventStoreInterface;
use Override;

/** Counts the appends it is asked for */
final class SpyEventStore implements EventStoreInterface
{
    public int $appends = 0;

    #[Override]
    public function append(Event $event): void
    {
        $this->appends++;
    }

    #[Override]
    public function appendAll(EventsInterface $events): void
    {
        $this->appends++;
    }

    #[Override]
    public function all(): EventsInterface
    {
        return new Events([]);
    }
}
