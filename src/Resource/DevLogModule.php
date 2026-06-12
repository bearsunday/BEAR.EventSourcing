<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\Resource\InvokerInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class DevLogModule extends AbstractModule
{
    private const INVOKER = 'bear_event_sourcing_invoker';

    public function __construct(
        private readonly string $viewDir,
        private readonly RecordedMethods|null $methods = null,
        private readonly SemanticLoggerInterface|null $logger = null,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        FileViewStore::clearDirectory($this->viewDir);

        $this->bind(RecordedMethods::class)->toInstance(
            $this->methods ?? new RecordedMethods(RecordedMethods::WITH_READS),
        );
        $this->bind(ViewStoreInterface::class)->toInstance(new FileViewStore($this->viewDir));

        if ($this->logger !== null) {
            $this->bind(SemanticLoggerInterface::class)->toInstance($this->logger);
        } else {
            $this->bind(SemanticLoggerInterface::class)->to(SemanticLogger::class)->in(Scope::SINGLETON);
        }

        $this->rename(InvokerInterface::class, self::INVOKER);
        $this->bind(InvokerInterface::class)
            ->toConstructor(SemanticLogInvoker::class, ['invoker' => self::INVOKER])
            ->in(Scope::SINGLETON);
    }
}
