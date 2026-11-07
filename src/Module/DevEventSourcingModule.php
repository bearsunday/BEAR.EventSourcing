<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\Resource\Invoker;
use BEAR\Resource\InvokerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Records every resource request into the cache log's session, so requests and their cache scopes read as one tree
 *
 * Install beside DevQueryRepositoryLogModule, which owns that session and writes it, and give both the same keep.
 */
final class DevEventSourcingModule extends AbstractModule
{
    private const string INVOKER = 'bear_event_sourcing_dev_invoker';

    /** @param string $dir response bodies are stored under it */
    public function __construct(
        private readonly string $dir,
        private readonly int $keep = 10,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        // An installed module cannot rename the app chain's invoker binding, so the inner invoker is named here.
        $this->bind(InvokerInterface::class)->annotatedWith(self::INVOKER)->to(Invoker::class);
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => self::INVOKER,
                'logger' => CacheLog::class,
                'recordedMethods' => Recorded::class,
                'paramsFilter' => Filtered::class,
            ])
            ->in(Scope::SINGLETON);
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
            ->toInstance(new RecordedMethods(RecordedMethods::WITH_READS));
        $this->bind(BodyStoreInterface::class)->toInstance(new FileBodyStore($this->dir, $this->keep));
    }
}
