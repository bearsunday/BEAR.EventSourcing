<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\EventSourcing;

use Countable;
use DateTimeInterface;
use IteratorAggregate;

/**
 * Events collection interface
 *
 * @extends IteratorAggregate<int, Event>
 */
interface EventsInterface extends IteratorAggregate, Countable
{
    /**
     * Create from JSON string
     */
    public static function fromJson(string $json): self;

    /**
     * Convert to JSON string
     */
    public function toJson(): string;

    /**
     * Add an event to the collection
     */
    public function add(Event $event): self;

    /**
     * Filter events by URI
     */
    public function filterByUri(string $pattern): self;

    /**
     * Filter events by method
     */
    public function filterByMethod(string $method): self;

    /**
     * Filter events since a specific time
     */
    public function since(DateTimeInterface $since): self;

    /**
     * Replay events with a handler
     *
     * @param callable(Event): void $handler
     */
    public function replay(callable $handler): void;

    /**
     * Get all events as array
     *
     * @return Event[]
     */
    public function all(): array;
}
