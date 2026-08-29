<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\Module\MediaQueryEventStoreModule;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\EventSourcing\SemanticLogExtractorInterface;
use BEAR\EventSourcing\Tests\Fixture\ResourceRequestContext;
use BEAR\EventSourcing\Tests\Fixture\ResourceResponseContext;
use BEAR\Resource\Module\ResourceClientModule;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use Ray\Di\Exception\Unbound;
use Ray\Di\Injector;

final class EventSourcingModuleTest extends TestCase
{
    public function testBindsSemanticLogExtractorInterface(): void
    {
        $injector = new Injector(new EventSourcingModule());

        $extractor = $injector->getInstance(SemanticLogExtractorInterface::class);

        $this->assertInstanceOf(SemanticLogExtractor::class, $extractor);
    }

    public function testUsesConfiguredRecordedMethods(): void
    {
        $injector = new Injector(new EventSourcingModule(
            methods: new RecordedMethods(RecordedMethods::WITH_READS),
        ));
        $extractor = $injector->getInstance(SemanticLogExtractorInterface::class);
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext('app://self/users/1', 'GET'));
        $logger->close(new ResourceResponseContext(200, ['id' => 1]), $openId);

        $events = $extractor->extract($logger->flush());

        $this->assertCount(1, $events);
    }

    public function testDevRecordingDoesNotWidenExtraction(): void
    {
        // The documented dev split: the bridge records GET (WITH_READS) while the
        // extractor's default policy must stay writes-only. One shared binding
        // key would silently widen extraction — the policies are separate keys.
        $injector = new Injector(new EventSourcingModule(module: new ResourceObservationModule(
            methods: new RecordedMethods(RecordedMethods::WITH_READS),
            module: new ResourceClientModule(),
        )));
        $extractor = $injector->getInstance(SemanticLogExtractorInterface::class);
        $logger = new SemanticLogger();
        $openId = $logger->open(new ResourceRequestContext('app://self/users/1', 'GET'));
        $logger->close(new ResourceResponseContext(200, ['id' => 1]), $openId);

        $events = $extractor->extract($logger->flush());

        $this->assertCount(0, $events);
        $this->assertSame('GET', $injector->getInstance(RecordedMethods::class, Recorded::class)->normalize('GET'));
    }

    public function testMissingMediaQueryInstallationFailsAtInjectionTime(): void
    {
        // The application owns MediaQuerySqlModule; forgetting it must surface as
        // an explicit unbound error, not as a store that fails on first use.
        $injector = new Injector(new MediaQueryEventStoreModule());

        $this->expectException(Unbound::class);
        $injector->getInstance(EventStoreInterface::class);
    }
}
