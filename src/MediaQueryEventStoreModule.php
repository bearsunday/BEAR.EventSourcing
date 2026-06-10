<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use BEAR\EventSourcing\Query\EventStoreQueryInterface;
use Ray\Di\AbstractModule;

final class MediaQueryEventStoreModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(EventStoreQueryInterface::class)->toNull();
        $this->bind(EventStoreInterface::class)->to(MediaQueryEventStore::class);
    }
}
