<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Caller;

use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\RenderInterface;
use Ray\Di\AbstractModule;

final class CallerAppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new ResourceModule(__NAMESPACE__));
        $this->bind(RenderInterface::class)->to(TemplateRenderer::class);
        $this->bind(SiteSetting::class);
    }
}
