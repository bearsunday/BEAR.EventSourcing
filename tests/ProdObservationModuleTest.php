<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Module\ProdObservationModule;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\EventSourcing\Resource\SemanticLogLinker;
use BEAR\EventSourcing\Store\InMemoryEventStore;
use BEAR\EventSourcing\Tests\Fixture\AppInvoker;
use BEAR\EventSourcing\Tests\Fixture\AppLinker;
use BEAR\EventSourcing\Tests\Fixture\UnserializableEventStore;
use BEAR\QueryRepository\Log\LogSinkInterface;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\LinkerInterface;
use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\ResourceInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;
use ReflectionProperty;

use function iterator_to_array;
use function serialize;
use function unserialize;

/**
 * @psalm-suppress NoInterfaceProperties,MixedFunctionCall,MixedMethodCall
 *     The resource client is driven through its magic verb properties.
 */
final class ProdObservationModuleTest extends TestCase
{
    private const APP = 'BEAR\EventSourcing\Tests\Fixture\Prod';

    public function testOnlyASucceededWriteIsStoredWhenTheLogIsFlushed(): void
    {
        $injector = $this->prodContext(InMemoryEventStore::class);
        $resource = $injector->getInstance(ResourceInterface::class);
        $resource->post->uri('app://self/order')(['orderId' => 'O-1']);
        $resource->get->uri('app://self/order')(['orderId' => 'O-1']);
        $resource->delete->uri('app://self/order')(['orderId' => 'O-1']);

        $this->flush($injector);

        $store = $injector->getInstance(EventStoreInterface::class);
        $this->assertInstanceOf(EventStoreInterface::class, $store);
        $events = iterator_to_array($store->all(), false);
        $this->assertCount(1, $events);
        $this->assertSame(['POST', 'app://self/order'], [$events[0]->method, $events[0]->uri]);
    }

    public function testTheLogSinkSerializesBesideAStoreThatCannot(): void
    {
        $injector = $this->prodContext(UnserializableEventStore::class);
        $injector->getInstance(EventStoreInterface::class);

        $sink = unserialize(serialize($injector->getInstance(LogSinkInterface::class)));

        $this->assertInstanceOf(LogSinkInterface::class, $sink);
    }

    public function testTheRecordingWrapsTheInvokerAndTheLinkerTheChainBinds(): void
    {
        $chain = new class (new ResourceModule(self::APP)) extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(InvokerInterface::class)->to(AppInvoker::class);
                $this->bind(LinkerInterface::class)->to(AppLinker::class);
            }
        };
        $injector = $this->prodContext(InMemoryEventStore::class, $chain);

        $invoker = $injector->getInstance(InvokerInterface::class);
        $this->assertInstanceOf(SemanticLogInvoker::class, $invoker);
        $this->assertInstanceOf(AppInvoker::class, (new ReflectionProperty($invoker, 'invoker'))->getValue($invoker));
        $linker = $injector->getInstance(LinkerInterface::class);
        $this->assertInstanceOf(SemanticLogLinker::class, $linker);
        $this->assertInstanceOf(AppLinker::class, (new ReflectionProperty($linker, 'linker'))->getValue($linker));
    }

    /**
     * A `prod-` context module the way BEAR.Package builds it, with the application's store binding beside the module
     *
     * @param class-string<EventStoreInterface> $store
     */
    private function prodContext(string $store, AbstractModule $chain = new ResourceModule(self::APP)): Injector
    {
        $context = new class ($store, $chain) extends AbstractModule {
            /** @param class-string<EventStoreInterface> $store */
            public function __construct(private readonly string $store, AbstractModule $chain)
            {
                parent::__construct($chain);
            }

            protected function configure(): void
            {
                $this->bind(EventStoreInterface::class)->to($this->store)->in(Scope::SINGLETON);
                $this->install(new ProdObservationModule($this));
            }
        };

        return new Injector($context);
    }

    /** What ProdQueryRepositoryLogModule's sink does when the process ends */
    private function flush(Injector $injector): void
    {
        $sink = $injector->getInstance(LogSinkInterface::class);
        $this->assertInstanceOf(LogSinkInterface::class, $sink);
        $logger = $injector->getInstance(SemanticLoggerInterface::class, CacheLog::class);
        $this->assertInstanceOf(SemanticLoggerInterface::class, $logger);
        $sink->flush($logger);
    }
}
