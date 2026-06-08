<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Logger;

use BEAR\Resource\LoggerInterface as ResourceLoggerInterface;
use Ray\Di\AbstractModule;

final class BaseLoggerModule extends AbstractModule
{
    public function __construct(
        private readonly RecordingResourceLogger $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->bind(ResourceLoggerInterface::class)->toInstance($this->logger);
    }
}
