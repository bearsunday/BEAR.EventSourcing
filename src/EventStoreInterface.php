<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

interface EventStoreInterface
{
    public function append(Event $event): void;

    public function appendAll(Events $events): void;

    public function all(): Events;
}
