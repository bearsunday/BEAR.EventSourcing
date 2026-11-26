<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\QueryRepository\DevQueryRepositoryLogModule;
use BEAR\RepositoryModule\Annotation\CacheLog;
use Ray\Di\AbstractModule;

/**
 * Writes the requests of the given chain, through the invoker and linker it binds, and their cache scopes as one tree
 * in the given directory, with response bodies under bodies/
 *
 * It installs DevQueryRepositoryLogModule there; an app installing that module too gets the first one's directory.
 */
final class DevObservationModule extends AbstractModule
{
    /**
     * @param AbstractModule|null $module the chain of the context module installing this one, `$this->lastModule`
     * @param int                 $keep   log sessions retained; body generations keep one more, so a retained log's
     *                                    body_ref resolves
     */
    public function __construct(
        private readonly string $dir,
        private readonly AbstractModule|null $module,
        private readonly int $keep = 10,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->install(new DevQueryRepositoryLogModule($this->dir, $this->keep));
        // The store creates a generation, and prunes the oldest, at the first stored body: before this request's log is
        // written and the oldest log pruned. One generation more than the logs keeps that log's bodies.
        $this->install(new ResourceObservationModule(
            methods: new RecordedMethods(RecordedMethods::WITH_READS),
            bodyStore: new FileBodyStore($this->dir . '/bodies', $this->keep + 1),
            module: $this->module,
            loggerQualifier: CacheLog::class,
        ));
        $this->bind()->annotatedWith(SemanticLogInvoker::RECORD_CALLER)->toInstance(true);
    }
}
