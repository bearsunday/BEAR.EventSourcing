<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\Resource\InvokerInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Name;
use Ray\Di\Scope;

final class ResourceObservationModule extends AbstractModule
{
    private const INVOKER = 'bear_event_sourcing_invoker';
    private const DECORATOR = 'bear_event_sourcing_decorator';

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
        if ($this->methods !== null) {
            $this->bind(RecordedMethods::class)->toInstance($this->methods);
        }

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

        // Renames requested during configure() are applied only after the wrapped
        // module is merged (ray/di >= 2.21), so the decorator cannot claim the
        // unnamed slot directly: bind it under its own name, move the incoming
        // invoker aside, then promote the decorator into the unnamed slot.
        $this->bind(InvokerInterface::class)
            ->annotatedWith(self::DECORATOR)
            ->toConstructor(SemanticLogInvoker::class, ['invoker' => self::INVOKER])
            ->in(Scope::SINGLETON);
        $this->rename(InvokerInterface::class, self::INVOKER);
        $this->rename(InvokerInterface::class, Name::ANY, self::DECORATOR);
    }
}
