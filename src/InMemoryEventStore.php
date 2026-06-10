<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

final class InMemoryEventStore implements EventStoreInterface
{
    private Events $events;

    public function __construct(Events|null $events = null)
    {
        $this->events = $events ?? new Events();
    }

    public function append(Event $event): void
    {
        $this->events = $this->events->add($event);
    }

    public function appendAll(Events $events): void
    {
        foreach ($events as $event) {
            $this->append($event);
        }
    }

    public function all(): Events
    {
        return $this->events;
    }
}
