<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\EventStoreExtractor;
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\InMemoryEventStore;
use BEAR\SemanticLogger\EventExtractorInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

final class EventSourcingModuleTest extends TestCase
{
    public function testBindEventStore(): void
    {
        $injector = new Injector(new EventSourcingModule());

        $store = $injector->getInstance(EventStoreInterface::class);

        $this->assertInstanceOf(InMemoryEventStore::class, $store);
    }

    public function testBindEventExtractor(): void
    {
        $injector = new Injector(new EventSourcingModule());

        $extractor = $injector->getInstance(EventExtractorInterface::class);

        $this->assertInstanceOf(EventStoreExtractor::class, $extractor);
    }

    public function testEventStoreIsNotSingleton(): void
    {
        $injector = new Injector(new EventSourcingModule());

        $store1 = $injector->getInstance(EventStoreInterface::class);
        $store2 = $injector->getInstance(EventStoreInterface::class);

        // Default Ray.Di behavior creates new instances
        $this->assertNotSame($store1, $store2);
    }
}
