<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Countable;
use IteratorAggregate;

/**
 * Interface for event collections.
 *
 * @extends IteratorAggregate<int, Event>
 */
interface EventsInterface extends IteratorAggregate, Countable
{
    /**
     * Replay events through a handler.
     *
     * @param callable(Event): void $handler
     */
    public function play(callable $handler): void;

    /**
     * Filter events by predicate.
     *
     * @param callable(Event): bool $predicate
     */
    public function filter(callable $predicate): self;

    /**
     * Convert to JSON string.
     */
    public function toJson(): string;
}
