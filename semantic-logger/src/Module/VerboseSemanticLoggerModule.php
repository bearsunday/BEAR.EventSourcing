<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Module;

use BEAR\Resource\Invoker;
use BEAR\Resource\InvokerInterface;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use BEAR\SemanticLogger\Invoker\DevSemanticInvoker;
use BEAR\SemanticLogger\Profile\Verbose\ContextFactory;
use Koriym\SemanticLogger\DevLogger;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

use function sys_get_temp_dir;

/**
 * Module for verbose semantic logging with full profiling and file persistence.
 *
 * Features:
 * - Verbose profile with XHProf, Xdebug, PHP profiling
 * - Immediate file persistence via DevLogger
 * - MCP server integration support
 *
 * @psalm-api
 */
final class VerboseSemanticLoggerModule extends AbstractModule
{
    public function __construct(
        private readonly string|null $logDir = null,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        // Bind SemanticLogger as singleton
        $this->bind(SemanticLoggerInterface::class)
            ->to(SemanticLogger::class)
            ->in(Scope::SINGLETON);

        // Bind DevLogger with configured directory
        $logDir = $this->logDir ?? sys_get_temp_dir();
        $this->bind(DevLogger::class)
            ->toInstance(new DevLogger($logDir));

        // Bind Verbose context factory
        $this->bind(ContextFactoryInterface::class)
            ->to(ContextFactory::class);

        // Bind original invoker for decorator pattern
        $this->bind(InvokerInterface::class)
            ->annotatedWith('original')
            ->to(Invoker::class);

        // Wrap with DevSemanticInvoker
        $this->bind(InvokerInterface::class)
            ->to(DevSemanticInvoker::class);
    }
}
