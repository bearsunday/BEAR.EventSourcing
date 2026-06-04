<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Logger;

use BEAR\EventSourcing\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Module\EventSourcingModule;
use Ray\Di\AbstractModule;

final class EventSourcingTestModule extends EventSourcingModule
{
    public function __construct(
        AbstractModule $module,
        private readonly RecordingEventStore $eventStore,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->bind(EventStoreInterface::class)->toInstance($this->eventStore);
    }
}
