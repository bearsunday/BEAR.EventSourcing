<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;

/**
 * Application module
 */
class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        // Install package module
        $this->install(new PackageModule());

        // Install database module
        $this->install(new DbModule($this->appMeta));

        // Install query module
        $this->install(new QueryModule());

        // Install event sourcing module
        $this->install(new EventSourcingModule());

        // Install service module
        $this->install(new ServiceModule());

        // Install authentication module
        $this->install(new AuthModule());

        // Install validation module
        $this->install(new ValidationModule());
    }
}
