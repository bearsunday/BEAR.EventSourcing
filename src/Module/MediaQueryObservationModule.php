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
 *
 * Deliberately no #[Filtered] ParamsFilterInterface binding here: the
 * adapter's parameter is nullable and falls back to SensitiveParamsFilter
 * when unbound, and a binding in this module would collide with the one
 * ResourceObservationModule makes — whichever installed first would win for
 * both recorders, silently replacing a custom filter with the default.
 */
final class MediaQueryObservationModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(MediaQueryLoggerInterface::class)
            ->to(SemanticLogMediaQueryLogger::class)->in(Scope::SINGLETON);
    }
}
