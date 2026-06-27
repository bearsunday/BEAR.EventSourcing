<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\EventSourcing\RecordedMethods;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Di\AbstractModule;

final class DevLogModule extends AbstractModule
{
    private readonly RecordedMethods $methods;

    public function __construct(
        private readonly string $bodyDir,
        RecordedMethods|null $methods = null,
        private readonly SemanticLoggerInterface|null $logger = null,
        private readonly AbstractModule|null $module = null,
    ) {
        // Clear at module construction, not at configure(), to avoid side effects during DI graph merges.
        FileBodyStore::clearDirectory($bodyDir);

        $this->methods = $methods ?? new RecordedMethods(RecordedMethods::WITH_READS);
        parent::__construct(null);
    }

    protected function configure(): void
    {
        $this->install(new ResourceObservationModule(
            methods: $this->methods,
            bodyStore: new FileBodyStore($this->bodyDir),
            logger: $this->logger,
            module: $this->module,
        ));
    }
}
