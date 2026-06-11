<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use ArrayIterator;
use Traversable;

use function count;

/** @psalm-import-type EventList from Types */
final readonly class Events implements EventsInterface
{
    /** @param EventList $events */
    public function __construct(private array $events = [])
    {
    }

    public function count(): int
    {
        return count($this->events);
    }

    /** @return Traversable<int, Event> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }
}
