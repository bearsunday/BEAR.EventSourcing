<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\MediaQuery\SemanticLogMediaQueryLogger;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\MediaQuery\MediaQueryLoggerInterface;

/**
 * Routes Ray.MediaQuery's logger seam into the semantic log.
 *
 * Installs flat: only the logger binding, never the application's
 * MediaQuery or database modules. install() keeps a binding the installer
 * already holds, so install this before the MediaQuery modules — or bind
 * from a context module, where a direct bind wins over the inner chain.
 */
final class MediaQueryObservationModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(MediaQueryLoggerInterface::class)
            ->to(SemanticLogMediaQueryLogger::class)->in(Scope::SINGLETON);
    }
}
