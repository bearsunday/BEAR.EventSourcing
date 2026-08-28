<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Store\MediaQueryEventStore;
use Ray\Di\AbstractModule;

/**
 * Binds EventStoreInterface to the SQL-backed store.
 *
 * EventStoreQueryInterface is intentionally left unbound here: the application
 * owns MediaQuerySqlModule (and its database), and a missing installation must
 * surface as an explicit unbound error at injection time, never as a store that
 * fails on first use.
 */
final class MediaQueryEventStoreModule extends AbstractModule
{
    protected function configure(): void
    {

        $this->bind(EventStoreInterface::class)->to(MediaQueryEventStore::class);
    }
}
