<?php

declare(strict_types=1);

namespace BearEccube\Module;

use BearEccube\EventSourcing\Event;
use BearEccube\EventSourcing\EventStore;
use BearEccube\EventSourcing\EventStoreInterface;
use BearEccube\EventSourcing\Events;
use BearEccube\EventSourcing\EventsInterface;
use BearEccube\Interceptor\EventSourcingInterceptor;
use Ray\Aop\Matcher;
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
        // Only non-GET methods (POST, PUT, DELETE) are recorded
        $this->bindInterceptor(
            $this->matcher->subclassesOf(\BEAR\Resource\ResourceObject::class),
            $this->matcher->logicalOr(
                $this->matcher->startsWith('onPost'),
                $this->matcher->logicalOr(
                    $this->matcher->startsWith('onPut'),
                    $this->matcher->startsWith('onDelete')
                )
            ),
            [EventSourcingInterceptor::class]
        );
    }
}
