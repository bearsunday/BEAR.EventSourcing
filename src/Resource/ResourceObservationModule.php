<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\Resource\InvokerInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class ResourceObservationModule extends AbstractModule
{
    private const string INVOKER = 'bear_event_sourcing_invoker';

    public function __construct(
        private readonly RecordedMethods|null $methods = null,
        private readonly BodyStoreInterface|null $bodyStore = null,
        private readonly SemanticLoggerInterface|null $logger = null,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
            ->toInstance($this->methods ?? new RecordedMethods());

        if ($this->bodyStore !== null) {
            $this->bind(BodyStoreInterface::class)->toInstance($this->bodyStore);
        } else {
            $this->bind(BodyStoreInterface::class)->to(NullBodyStore::class)->in(Scope::SINGLETON);
        }

        if ($this->logger !== null) {
            $this->bind(SemanticLoggerInterface::class)->toInstance($this->logger);
        } else {
            $this->bind(SemanticLoggerInterface::class)->to(SemanticLogger::class)->in(Scope::SINGLETON);
        }

        $this->rename(InvokerInterface::class, self::INVOKER);
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, [
                'invoker' => self::INVOKER,
                'recordedMethods' => Recorded::class,
            ])
            ->in(Scope::SINGLETON);
    }
}
