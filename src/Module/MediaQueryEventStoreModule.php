<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Query\EventStoreQueryInterface;
use BEAR\EventSourcing\Store\MediaQueryEventStore;
use Ray\Di\AbstractModule;

final class MediaQueryEventStoreModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(EventStoreQueryInterface::class)->toNull();
        $this->bind(EventStoreInterface::class)->to(MediaQueryEventStore::class);
    }
}
