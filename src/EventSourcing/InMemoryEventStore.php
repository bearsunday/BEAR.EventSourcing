<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use DateTimeInterface;

use function array_filter;
use function array_values;
use function sprintf;
use function str_starts_with;

/**
 * In-memory Event Store implementation for tests and transient use.
 */
final class InMemoryEventStore implements EventStoreInterface
{
    private EventsInterface $events;

    public function __construct(EventsInterface|null $events = null)
    {
        $this->events = $events ?? new Events();
    }

    /** @inheritDoc */
    public function append(Event $event): void
    {
        $this->events = $this->events->add($event);
    }

    /** @inheritDoc */
    public function getEvents(): EventsInterface
    {
        return $this->events;
    }

    /** @inheritDoc */
    public function getEventsSince(DateTimeInterface $since): EventsInterface
    {
        return $this->events->since($since);
    }

    /** @inheritDoc */
    public function getEventsByUri(string $pattern): EventsInterface
    {
        return $this->events->filterByUri($pattern);
    }

    /** @inheritDoc */
    public function getEventsByAggregateId(string $aggregateType, string $aggregateId): EventsInterface
    {
        $uri = sprintf('/%s/%s', $aggregateType, $aggregateId);
        $childrenPrefix = sprintf('%s/', $uri);
        $events = array_filter(
            $this->events->all(),
            static fn (Event $event): bool => $event->uri === $uri || str_starts_with($event->uri, $childrenPrefix),
        );

        return new Events(array_values($events));
    }
}
