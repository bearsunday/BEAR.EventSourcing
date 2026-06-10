<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

/** @psalm-import-type EventList from Types */
final class InMemoryEventStore implements EventStoreInterface
{
    /** @var EventList */
    private array $events = [];

    public function __construct(EventsInterface|null $events = null)
    {
        if ($events === null) {
            return;
        }

        foreach ($events as $event) {
            $this->events[] = $event;
        }
    }

    public function append(Event $event): void
    {
        $this->events[] = $event;
    }

    public function appendAll(EventsInterface $events): void
    {
        foreach ($events as $event) {
            $this->append($event);
        }
    }

    public function all(): EventsInterface
    {
        return new Events($this->events);
    }
}
