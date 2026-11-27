<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\NullBodyStore;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\EventSourcing\Resource\SemanticLogLinker;
use BEAR\EventSourcing\Store\EventStoreLogWriter;
use BEAR\QueryRepository\Log\LogWriterInterface;
use BEAR\QueryRepository\ProdQueryRepositoryLogModule;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\LinkerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\MultiBinder;
use Ray\Di\Scope;

/**
 * Stores the write requests of the installing module's chain as events in the EventStoreInterface the application
 * binds, when ProdQueryRepositoryLogModule flushes its log at the end of the process
 */
final class ProdObservationModule extends AbstractModule
{
    private const INVOKER = 'bear_event_sourcing_prod_invoker';
    private const LINKER = 'bear_event_sourcing_prod_linker';

    /** @param AbstractModule $module the module installing this one, `$this` */
    public function __construct(AbstractModule $module)
    {
        $module->rename(InvokerInterface::class, self::INVOKER);
        $module->rename(LinkerInterface::class, self::LINKER);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->install(new ProdQueryRepositoryLogModule());
        $this->install(new EventSourcingModule());
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => self::INVOKER,
                'logger' => CacheLog::class,
                'recordedMethods' => Recorded::class,
                'paramsFilter' => Filtered::class,
            ])
            ->in(Scope::SINGLETON);
        $this->bind(LinkerInterface::class)
            ->toConstructor(SemanticLogLinker::class, [
                'linker' => self::LINKER,
                'logger' => CacheLog::class,
                'recordedMethods' => Recorded::class,
            ])
            ->in(Scope::SINGLETON);
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)->toInstance(new RecordedMethods());
        $this->bind(BodyStoreInterface::class)->to(NullBodyStore::class)->in(Scope::SINGLETON);
        // A compiled injector builds only bound classes, a multibinding's target among them
        $this->bind(EventStoreLogWriter::class);
        MultiBinder::newInstance($this, LogWriterInterface::class)
            ->addBinding(EventStoreLogWriter::class)
            ->to(EventStoreLogWriter::class);
    }
}
