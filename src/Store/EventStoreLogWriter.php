<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Store;

use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\SemanticLogExtractorInterface;
use BEAR\QueryRepository\Log\LogWriterInterface;
use Koriym\SemanticLogger\LogJson;
use Override;
use Ray\Di\Di\Set;
use Ray\Di\ProviderInterface;

/** Appends the events extracted from a flushed log to the EventStoreInterface the application binds */
final class EventStoreLogWriter implements LogWriterInterface
{
    /** @param ProviderInterface<EventStoreInterface> $store taken when writing, so a serialized log sink holds no store */
    public function __construct(
        private readonly SemanticLogExtractorInterface $extractor,
        #[Set(EventStoreInterface::class)]
        private readonly ProviderInterface $store,
    ) {
    }

    #[Override]
    public function write(LogJson $log): void
    {
        $this->store->get()->appendAll($this->extractor->extract($log));
    }
}
