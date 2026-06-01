<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\EventSourcing\Events;
use BEAR\EventSourcing\EventSourcing\EventsInterface;
use BEAR\EventSourcing\EventSourcing\EventStore;
use BEAR\EventSourcing\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Interceptor\EventSourcingInterceptor;
use BEAR\Resource\ResourceObject;
use Ray\Di\AbstractModule;

/**
 * Event Sourcing module
 */
class EventSourcingModule extends AbstractModule
{
    protected function configure(): void
    {
        // Bind event sourcing interfaces
        $this->bind(EventStoreInterface::class)->to(EventStore::class);
        $this->bind(EventsInterface::class)->to(Events::class);

        // Bind interceptor to resources that should be recorded
        // Only non-GET methods (POST, PUT, PATCH, DELETE) are recorded
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->logicalOr(
                $this->matcher->startsWith('onPost'),
                $this->matcher->logicalOr(
                    $this->matcher->startsWith('onPut'),
                    $this->matcher->logicalOr(
                        $this->matcher->startsWith('onPatch'),
                        $this->matcher->startsWith('onDelete'),
                    ),
                ),
            ),
            [EventSourcingInterceptor::class],
        );
    }
}
