<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\LinkerInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class ResourceObservationModule extends AbstractModule
{
    private const INVOKER = 'bear_event_sourcing_invoker';
    private const LINKER = 'bear_event_sourcing_linker';

    public function __construct(
        private readonly RecordedMethods|null $methods = null,
        private readonly BodyStoreInterface|null $bodyStore = null,
        private readonly SemanticLoggerInterface|null $logger = null,
        AbstractModule|null $module = null,
        private readonly ParamsFilterInterface|null $paramsFilter = null,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(RecordedMethods::class)->annotatedWith(Recorded::class)
            ->toInstance($this->methods ?? new RecordedMethods());
        $this->bind(ParamsFilterInterface::class)->annotatedWith(Filtered::class)
            ->toInstance($this->paramsFilter ?? new SensitiveParamsFilter());

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
                'paramsFilter' => Filtered::class,
                'recordCaller' => SemanticLogInvoker::RECORD_CALLER,
            ])
            ->in(Scope::SINGLETON);
        // A wrapped module made of an invoker alone binds no linker to decorate
        $wrapped = $this->lastModule instanceof AbstractModule ? $this->lastModule->getContainer()->getContainer() : [];
        if (! isset($wrapped[LinkerInterface::class . '-'])) {
            return;
        }

        $this->rename(LinkerInterface::class, self::LINKER);
        $this->bind(LinkerInterface::class)
            ->toConstructor(SemanticLogLinker::class, ['linker' => self::LINKER])
            ->in(Scope::SINGLETON);
    }
}
