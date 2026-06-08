<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Module;

use BEAR\Resource\LoggerInterface;
use BEAR\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLogger as KoriymSemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class SemanticLoggerModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(SemanticLoggerInterface::class)
             ->to(KoriymSemanticLogger::class)
             ->in(Scope::SINGLETON);

        $this->bind(LoggerInterface::class)
             ->to(SemanticLogger::class);
    }
}
