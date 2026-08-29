<?php

declare(strict_types=1);

namespace FakeApp;

use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Module\MediaQueryEventStoreModule;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\MediaQuerySqlModule;

/**
 * Application-owned wiring for the SQL EventStore, mirroring the package's
 * recommended split: MediaQuery and the database stay in the application.
 */
final class EventStoreModule extends AbstractModule
{
    public function __construct(
        private readonly string $databaseFile,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->install(new AuraSqlModule('sqlite:' . $this->databaseFile));
        $this->install(new MediaQuerySqlModule(
            interfaceDir: $this->projectDir . '/src/Query',
            sqlDir: $this->projectDir . '/sql/event_store',
        ));
        $this->install(new EventSourcingModule());
        $this->install(new MediaQueryEventStoreModule());
    }
}
