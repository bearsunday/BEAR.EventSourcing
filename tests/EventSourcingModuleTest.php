<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Module\EventSourcingModule;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\EventSourcing\SemanticLogExtractorInterface;
use BEAR\EventSourcing\Tests\Fixture\ResourceRequestContext;
use BEAR\EventSourcing\Tests\Fixture\ResourceResponseContext;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
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
}
