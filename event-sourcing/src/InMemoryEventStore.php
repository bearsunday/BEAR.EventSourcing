<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

/**
 * In-memory event store for testing and development.
 */
final class InMemoryEventStore implements EventStoreInterface
{
    /** @var list<Event> */
    private array $events = [];

    public function append(Event $event): void
    {
        $this->events[] = $event;
    }

    public function getEvents(string $uri): Events
    {
        $filtered = [];
        foreach ($this->events as $event) {
            if ($event->uri === $uri) {
                $filtered[] = $event;
            }
        }

        return new Events($filtered);
    }

    public function getEventsSince(string $timestamp): Events
    {
        $filtered = [];
        foreach ($this->events as $event) {
            if ($event->timestamp >= $timestamp) {
                $filtered[] = $event;
            }
        }

        return new Events($filtered);
    }

    public function getAllEvents(): Events
    {
        return new Events($this->events);
    }

    /**
     * Clear all events (useful for testing).
     */
    public function clear(): void
    {
        $this->events = [];
    }
}
