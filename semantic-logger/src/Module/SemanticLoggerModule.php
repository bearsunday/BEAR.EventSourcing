<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Module;

use BEAR\Resource\Invoker;
use BEAR\Resource\InvokerInterface;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use BEAR\SemanticLogger\Invoker\SemanticInvoker;
use BEAR\SemanticLogger\Profile\Compact\ContextFactory;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Module for semantic logging with Compact profile.
 *
 * Binds:
 * - SemanticLoggerInterface -> SemanticLogger (singleton)
 * - ContextFactoryInterface -> Compact\ContextFactory
 * - InvokerInterface -> SemanticInvoker (wrapping original)
 */
class SemanticLoggerModule extends AbstractModule
{
    protected function configure(): void
    {
        // Bind SemanticLogger as singleton
        $this->bind(SemanticLoggerInterface::class)
            ->to(SemanticLogger::class)
            ->in(Scope::SINGLETON);

        // Bind Compact context factory
        $this->bind(ContextFactoryInterface::class)
            ->to(ContextFactory::class);

        // Bind original invoker for decorator pattern
        $this->bind(InvokerInterface::class)
            ->annotatedWith('original')
            ->to(Invoker::class);

        // Wrap with SemanticInvoker
        $this->bind(InvokerInterface::class)
            ->to(SemanticInvoker::class);
    }
}
