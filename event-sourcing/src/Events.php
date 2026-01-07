<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use ArrayIterator;
use Traversable;

use function array_filter;
use function array_values;
use function count;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Collection of events with replay capabilities.
 */
final class Events implements EventsInterface
{
    /**
     * @param list<Event> $events
     */
    public function __construct(
        private readonly array $events = [],
    ) {
    }

    /**
     * Create events from JSON string.
     */
    public static function fromJson(string $json): self
    {
        /** @var list<array{id: string, timestamp: string, uri: string, method: string, params: array<string, mixed>, result: mixed}> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $events = [];
        foreach ($data as $eventData) {
            $events[] = Event::fromArray($eventData);
        }

        return new self($events);
    }

    #[\Override]
    public function toJson(): string
    {
        return json_encode($this->events, JSON_THROW_ON_ERROR);
    }

    /**
     * @return Traversable<int, Event>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }

    #[\Override]
    public function count(): int
    {
        return count($this->events);
    }

    #[\Override]
    public function play(callable $handler): void
    {
        foreach ($this->events as $event) {
            $handler($event);
        }
    }

    #[\Override]
    public function filter(callable $predicate): EventsInterface
    {
        return new self(array_values(array_filter($this->events, $predicate)));
    }

    /**
     * Add an event to the collection.
     */
    public function add(Event $event): self
    {
        return new self([...$this->events, $event]);
    }

    /**
     * Get all events as array.
     *
     * @return list<Event>
     */
    public function toArray(): array
    {
        return $this->events;
    }
}
