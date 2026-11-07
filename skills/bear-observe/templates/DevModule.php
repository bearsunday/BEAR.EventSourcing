<?php

declare(strict_types=1);

namespace __NAMESPACE__\Module;

use BEAR\EventSourcing\Module\DevEventSourcingModule;
use BEAR\Package\AbstractAppModule;
use Override;

/** Observation context: `dev-` prefix on the app context (`dev-hal-app`, `cli-dev-hal-app`). */
final class DevModule extends AbstractAppModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new DevEventSourcingModule($this->appMeta->logDir . '/events'));
    }
}
