<?php

declare(strict_types=1);

namespace __NAMESPACE__\Module;

use BEAR\EventSourcing\Module\DevEventSourcingModule;
use BEAR\Package\AbstractAppModule;
use BEAR\QueryRepository\DevQueryRepositoryLogModule;
use Override;

/** Observation context: `dev-` prefix on the app context (`dev-hal-app`, `cli-dev-hal-app`). */
final class DevModule extends AbstractAppModule
{
    // Body generations and cache-log sessions are pruned by separate counters; keep
    // them equal so no retained log points at a pruned body.
    private const KEEP = 10;

    #[Override]
    protected function configure(): void
    {
        $this->install(new DevQueryRepositoryLogModule($this->appMeta->logDir . '/query-repository', self::KEEP));
        $this->install(new DevEventSourcingModule($this->appMeta->logDir . '/events', self::KEEP));
    }
}
