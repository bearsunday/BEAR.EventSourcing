<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\EventStore;
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Events;
use BEAR\EventSourcing\EventsInterface;
use Ray\Di\AbstractModule;

final class EventSourcingModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(EventStoreInterface::class)->to(EventStore::class);
        $this->bind(EventsInterface::class)->to(Events::class);
    }
}
