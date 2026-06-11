<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Ray\Di\AbstractModule;

final class EventSourcingModule extends AbstractModule
{
    public function __construct(
        private readonly RecordedMethods|null $methods = null,
        private readonly AbstractModule|null $store = null,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        if ($this->methods !== null) {
            $this->bind(RecordedMethods::class)->toInstance($this->methods);
        }

        $this->bind(SemanticLogExtractorInterface::class)->to(SemanticLogExtractor::class);

        if ($this->store !== null) {
            $this->install($this->store);
        }
    }
}
