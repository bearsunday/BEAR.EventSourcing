<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\MediaQuerySqlModule;

final class MediaQueryEventStoreModule extends AbstractModule
{
    public function __construct(
        private readonly string $databaseFile,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $projectDir = dirname(__DIR__, 2);
        $this->install(new MediaQuerySqlModule(
            interfaceDir: $projectDir . '/src/Query',
            sqlDir: $projectDir . '/sql/event_store',
        ));
        $this->install(new AuraSqlModule('sqlite:' . $this->databaseFile));
    }
}
