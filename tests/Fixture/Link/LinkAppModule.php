<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Link;

use BEAR\Resource\Module\ResourceModule;
use Ray\Di\AbstractModule;

final class LinkAppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new ResourceModule(__NAMESPACE__));
    }
}
