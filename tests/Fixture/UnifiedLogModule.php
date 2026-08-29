<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use BEAR\QueryRepository\QueryRepositoryModule;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\RepositoryModule\Annotation\EtagPool;
use BEAR\RepositoryModule\Annotation\ResourceObjectPool;
use BEAR\Resource\Module\ResourceModule;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * One request, one tree: the BEAR.QueryRepository cache log and the
 * BEAR.EventSourcing observation bridge share a single logger instance, so
 * cache scopes nest inside the resource_request scopes that caused them.
 * Wrap this module with DevLogModule/ResourceObservationModule passing the
 * same instance as `logger:`.
 */
final class UnifiedLogModule extends AbstractModule
{
    public function __construct(
        private readonly SemanticLoggerInterface $logger,
        private readonly string $storageDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->bind()->annotatedWith('storage_dir')->toInstance($this->storageDir)->in(Scope::SINGLETON);
        $this->install(new ResourceModule('FakeVendor\Unified'));
        $this->install(new QueryRepositoryModule());
        // Real in-memory pools: the default NullAdapter would make every GET miss.
        $this->bind(AdapterInterface::class)->annotatedWith(ResourceObjectPool::class)->to(ArrayAdapter::class);
        $this->bind(AdapterInterface::class)->annotatedWith(EtagPool::class)->to(ArrayAdapter::class);
        // The same instance under the CacheLog key; a `to()` singleton would
        // create a second logger per binding key and split the tree in two.
        $this->bind(SemanticLoggerInterface::class)->annotatedWith(CacheLog::class)->toInstance($this->logger);
    }
}
