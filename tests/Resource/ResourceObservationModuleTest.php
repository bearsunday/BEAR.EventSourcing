<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\Module\ResourceClientModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

final class ResourceObservationModuleTest extends TestCase
{
    public function testDecoratesResourceInvoker(): void
    {
        $injector = new Injector(new ResourceObservationModule(module: new ResourceClientModule()));

        $invoker = $injector->getInstance(InvokerInterface::class);

        $this->assertInstanceOf(SemanticLogInvoker::class, $invoker);
    }
}
