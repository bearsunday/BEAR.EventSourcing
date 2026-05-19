<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use DateTimeInterface;

interface EventStoreInterface
{
    public function append(Event $event): void;

    /**
     * Append every event in a single database transaction. If any single
     * append fails the whole batch is rolled back.
     */
    public function appendAll(EventsInterface $events): void;

    public function getEvents(): EventsInterface;

    public function getEventsSince(DateTimeInterface $since): EventsInterface;

    public function getEventsByUri(string $pattern): EventsInterface;

    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface;
}
