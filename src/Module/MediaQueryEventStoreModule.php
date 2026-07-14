<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Store\MediaQueryEventStore;
use Ray\Di\AbstractModule;

final class MediaQueryEventStoreModule extends AbstractModule
{
    protected function configure(): void
    {
        // EventStoreQueryInterface is bound by the application's MediaQuerySqlModule
        // (interfaceDir scan). Leaving it unbound here makes a missing wiring fail
        // as a clear Ray.Di Unbound error instead of a confusing runtime TypeError.
        $this->bind(EventStoreInterface::class)->to(MediaQueryEventStore::class);
    }
}
