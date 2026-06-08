<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Module;

use BEAR\Resource\InvokerInterface;
use BEAR\Resource\Module\ResourceClientModule;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use BEAR\SemanticLogger\Invoker\SemanticInvoker;
use BEAR\SemanticLogger\Profile\Compact\ContextFactory;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

final class SemanticLoggerModuleTest extends TestCase
{
    private Injector $injector;

    protected function setUp(): void
    {
        $this->injector = new Injector(new SemanticLoggerModule());
    }

    public function testBindSemanticLogger(): void
    {
        $logger = $this->injector->getInstance(SemanticLoggerInterface::class);

        $this->assertInstanceOf(SemanticLoggerInterface::class, $logger);
    }

    public function testBindContextFactory(): void
    {
        $factory = $this->injector->getInstance(ContextFactoryInterface::class);

        $this->assertInstanceOf(ContextFactory::class, $factory);
    }

    public function testBindInvoker(): void
    {
        // Test with ResourceClientModule to provide Invoker dependencies
        $module = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new ResourceClientModule());
                $this->override(new SemanticLoggerModule());
            }
        };
        $injector = new Injector($module);

        $invoker = $injector->getInstance(InvokerInterface::class);

        $this->assertInstanceOf(SemanticInvoker::class, $invoker);
    }

    public function testSemanticLoggerIsSingleton(): void
    {
        $logger1 = $this->injector->getInstance(SemanticLoggerInterface::class);
        $logger2 = $this->injector->getInstance(SemanticLoggerInterface::class);

        $this->assertSame($logger1, $logger2);
    }
}
