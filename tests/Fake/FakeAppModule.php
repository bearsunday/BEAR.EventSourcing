<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Fake;

use BEAR\Resource\Module\ResourceModule;
use BEAR\SemanticLogger\Module\SemanticLoggerModule;
use Ray\Di\AbstractModule;

final class FakeAppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new ResourceModule(__NAMESPACE__));
        $this->override(new SemanticLoggerModule());
    }
}
