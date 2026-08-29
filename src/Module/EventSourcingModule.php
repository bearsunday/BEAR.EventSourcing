<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\Extracted;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\EventSourcing\SemanticLogExtractorInterface;
use Ray\Di\AbstractModule;

final class EventSourcingModule extends AbstractModule
{
    public function __construct(
        private readonly RecordedMethods|null $methods = null,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(RecordedMethods::class)->annotatedWith(Extracted::class)
            ->toInstance($this->methods ?? new RecordedMethods());
        $this->bind(SemanticLogExtractorInterface::class)->to(SemanticLogExtractor::class);
    }
}
