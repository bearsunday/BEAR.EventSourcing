<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use DateTimeInterface;

/**
 * Event Store interface for persisting events
 */
interface EventStoreInterface
{
    /**
     * Append an event to the store
     */
    public function append(Event $event): void;

    /**
     * Get all events
     */
    public function getEvents(): EventsInterface;

    /**
     * Get events since a specific time
     */
    public function getEventsSince(DateTimeInterface $since): EventsInterface;

    /**
     * Get events by URI pattern
     */
    public function getEventsByUri(string $pattern): EventsInterface;

    /**
     * Get events by aggregate ID (e.g., order ID, customer ID)
     */
    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface;
}
