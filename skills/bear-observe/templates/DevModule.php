<?php

declare(strict_types=1);

namespace __NAMESPACE__\Module;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\Package\AbstractAppModule;
use BEAR\QueryRepository\DevQueryRepositoryLogModule;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\Resource\InvokerInterface;
use Override;
use Ray\Di\Scope;

/**
 * Observation context: `dev-` prefix on the app context (`dev-hal-app`, `cli-dev-hal-app`).
 *
 * A context module inherits the whole inner chain, so it renames the chain's own
 * InvokerInterface and decorates it in place. Re-providing a wrapped PackageModule here
 * registers the package pointcuts a second time and every interceptor runs twice — the log
 * shows it as a scope nested in itself.
 */
final class DevModule extends AbstractAppModule
{
    private const ORIGINAL_INVOKER = 'original_invoker';
    // Body generations and cache-log sessions are pruned by separate counters; keep
    // them equal so no retained log points at a pruned body.
    private const KEEP = 10;

    #[Override]
    protected function configure(): void
    {
        // The cache log module owns the session logger and the shutdown flush, so the
        // application writes no flush of its own.
        $this->install(new DevQueryRepositoryLogModule($this->appMeta->logDir . '/query-repository', self::KEEP));

        $this->rename(InvokerInterface::class, self::ORIGINAL_INVOKER);
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => self::ORIGINAL_INVOKER,
                // The cache log's logger: cache scopes nest inside the resource_request that caused them.
                'logger' => CacheLog::class,
                'recordedMethods' => Recorded::class,
                // Without this entry Ray.Di ignores the #[Filtered] attribute on the parameter
                // and an application-bound filter never reaches the invoker.
                'paramsFilter' => Filtered::class,
            ])
            ->in(Scope::SINGLETON);
        // GET is not a state change, so extraction ignores it; recording it is what makes
        // reads visible in the tree.
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
            ->toInstance(new RecordedMethods(RecordedMethods::WITH_READS));
        $this->bind(BodyStoreInterface::class)
            ->toInstance(new FileBodyStore($this->appMeta->logDir . '/es-bodies', self::KEEP));
    }
}
