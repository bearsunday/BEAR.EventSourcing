<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

/**
 * Interface for event storage.
 *
 * Implementations may use different backends:
 * - InMemory (for testing/development)
 * - File (JSON files)
 * - PDO (SQL databases)
 * - Redis
 */
interface EventStoreInterface
{
    /**
     * Append an event to the store.
     */
    public function append(Event $event): void;

    /**
     * Get all events for a specific URI.
     */
    public function getEvents(string $uri): Events;

    /**
     * Get all events since a specific timestamp.
     */
    public function getEventsSince(string $timestamp): Events;

    /**
     * Get all events in the store.
     */
    public function getAllEvents(): Events;
}
