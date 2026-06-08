<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Logger;

use BEAR\EventSourcing\EventSourcing\Event;
use BEAR\EventSourcing\EventSourcing\EventsInterface;
use BEAR\EventSourcing\EventSourcing\EventStoreInterface;
use DateTimeInterface;
use PHPUnit\Framework\Assert;

final class RecordingEventStore implements EventStoreInterface
{
    public Event|null $event = null;

    public function append(Event $event): void
    {
        $this->event = $event;
    }

    public function getEvents(): EventsInterface
    {
        Assert::fail('Not used');
    }

    public function getEventsSince(DateTimeInterface $since): EventsInterface
    {
        Assert::fail('Not used');
    }

    public function getEventsByUri(string $pattern): EventsInterface
    {
        Assert::fail('Not used');
    }

    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface
    {
        Assert::fail('Not used');
    }
}
