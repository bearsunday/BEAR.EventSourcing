<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Store;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\EventsInterface;
use BEAR\EventSourcing\EventStoreInterface;

use function array_values;

final class InMemoryEventStore implements EventStoreInterface
{
    /** @var array<string, Event> Keyed by Event::$id — appending the same event twice is a no-op. */
    private array $events = [];

    public function __construct(EventsInterface|null $events = null)
    {
        if ($events === null) {
            return;
        }

        $this->appendAll($events);
    }

    public function append(Event $event): void
    {
        if (isset($this->events[$event->id])) {
            return;
        }

        $this->events[$event->id] = $event;
    }

    public function appendAll(EventsInterface $events): void
    {
        foreach ($events as $event) {
            $this->append($event);
        }
    }

    public function all(): EventsInterface
    {
        return new Events(array_values($this->events));
    }
}
