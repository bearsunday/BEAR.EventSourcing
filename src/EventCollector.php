<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Koriym\SemanticLogger\SemanticLoggerInterface;

/**
 * Turns the current observation session into events at a request boundary.
 *
 * The application owns the flush; this class packages flush -> extract ->
 * optional append into one call for the application's request-end handler
 * (FPM shutdown, a Swoole request close, a RoadRunner worker loop iteration),
 * so long-running runtimes need no process-shutdown hook. A request that
 * recorded nothing flushes to an empty log and yields empty events, and
 * because every store treats append as idempotent per event id, a retried
 * invocation never duplicates facts.
 */
final readonly class EventCollector
{
    public function __construct(
        private SemanticLoggerInterface $logger,
        private SemanticLogExtractorInterface $extractor,
        private EventStoreInterface|null $store = null,
    ) {
    }

    public function __invoke(): EventsInterface
    {
        $events = $this->extractor->extract($this->logger->flush());
        $this->store?->appendAll($events);

        return $events;
    }
}
