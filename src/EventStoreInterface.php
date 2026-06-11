<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

/** Optional persistence port for already-extracted events. */
interface EventStoreInterface
{
    public function append(Event $event): void;

    public function appendAll(EventsInterface $events): void;

    public function all(): EventsInterface;
}
