<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\QueryRepository\DevQueryRepositoryLogModule;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\Resource\Invoker;
use BEAR\Resource\InvokerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Writes requests and their cache scopes as one tree in the given directory, with response bodies under bodies/
 *
 * It installs DevQueryRepositoryLogModule there; an app installing that module too gets the first one's directory.
 */
final class DevObservationModule extends AbstractModule
{
    private const INVOKER = 'bear_event_sourcing_dev_invoker';

    /** @param int $keep log sessions retained; body generations keep one more, so a retained log's body_ref resolves */
    public function __construct(
        private readonly string $dir,
        private readonly int $keep = 10,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->install(new DevQueryRepositoryLogModule($this->dir, $this->keep));
        // An installed module cannot rename the app chain's invoker binding, so the inner invoker is named here.
        $this->bind(InvokerInterface::class)->annotatedWith(self::INVOKER)->to(Invoker::class);
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => self::INVOKER,
                'logger' => CacheLog::class,
                'recordedMethods' => Recorded::class,
                'paramsFilter' => Filtered::class,
                'recordCaller' => SemanticLogInvoker::RECORD_CALLER,
            ])
            ->in(Scope::SINGLETON);
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
            ->toInstance(new RecordedMethods(RecordedMethods::WITH_READS));
        // The store creates a generation, and prunes the oldest, at the first stored body: before this request's log is
        // written and the oldest log pruned. One generation more than the logs keeps that log's bodies.
        $this->bind(BodyStoreInterface::class)->toInstance(new FileBodyStore($this->dir . '/bodies', $this->keep + 1));
        $this->bind()->annotatedWith(SemanticLogInvoker::RECORD_CALLER)->toInstance(true);
    }
}
