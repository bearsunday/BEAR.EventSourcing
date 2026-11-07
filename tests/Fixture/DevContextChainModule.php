<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use BEAR\QueryRepository\QueryRepositoryModule;
use BEAR\RepositoryModule\Annotation\EtagPool;
use BEAR\RepositoryModule\Annotation\ResourceObjectPool;
use BEAR\Resource\Module\ResourceModule;
use Ray\Di\AbstractModule;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/** The application chain a `dev-` context module wraps: resources, the cache, and real in-memory pools */
final class DevContextChainModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new ResourceModule('FakeVendor\Unified'));
        $this->install(new QueryRepositoryModule());
        $this->bind(AdapterInterface::class)->annotatedWith(ResourceObjectPool::class)->to(ArrayAdapter::class);
        $this->bind(AdapterInterface::class)->annotatedWith(EtagPool::class)->to(ArrayAdapter::class);
    }
}
