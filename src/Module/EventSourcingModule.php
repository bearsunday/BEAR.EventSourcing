<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\EventSourcing\Events;
use BEAR\EventSourcing\EventSourcing\EventsInterface;
use BEAR\EventSourcing\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\EventSourcing\SqlEventStore;
use BEAR\EventSourcing\Logger\EventSourcingLogger;
use BEAR\Resource\LoggerInterface as ResourceLoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Event Sourcing module
 */
class EventSourcingModule extends AbstractModule
{
    private const LOGGER = 'event_sourcing_logger';

    protected function configure(): void
    {
        // Bind event sourcing interfaces
        $this->bind(EventStoreInterface::class)->to(SqlEventStore::class);
        $this->bind(EventsInterface::class)->to(Events::class);

        // Keep the already configured resource logger and decorate it.
        $this->rename(ResourceLoggerInterface::class, self::LOGGER);
        $this->bind(ResourceLoggerInterface::class)
            ->toConstructor(EventSourcingLogger::class, ['logger' => self::LOGGER])
            ->in(Scope::SINGLETON);
    }
}
