<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\EventStoreExtractor;
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\InMemoryEventStore;
use BEAR\SemanticLogger\EventExtractorInterface;
use Ray\Di\AbstractModule;

/**
 * Module for Event Sourcing with InMemory store.
 *
 * For production, extend this module or create a custom one
 * that binds a persistent EventStoreInterface implementation.
 */
class EventSourcingModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(EventStoreInterface::class)
            ->to(InMemoryEventStore::class);

        $this->bind(EventExtractorInterface::class)
            ->to(EventStoreExtractor::class);
    }
}
