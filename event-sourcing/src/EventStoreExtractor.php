<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use BEAR\SemanticLogger\Context\AbstractCompleteContext;
use BEAR\SemanticLogger\Context\AbstractOpenContext;
use BEAR\SemanticLogger\EventExtractorInterface;
use Override;

use function strtoupper;

/**
 * Extracts events from resource operations and stores them.
 *
 * This class bridges BEAR.SemanticLogger with BEAR.EventSourcing,
 * implementing the EventExtractorInterface defined in SemanticLogger.
 *
 * @psalm-api
 */
final class EventStoreExtractor implements EventExtractorInterface
{
    public function __construct(
        private readonly EventStoreInterface $eventStore,
    ) {
    }

    #[Override]
    public function extract(
        AbstractOpenContext $open,
        AbstractCompleteContext $complete,
    ): void {
        // Skip GET requests - they don't change state
        if (strtoupper($open->method) === 'GET') {
            return;
        }

        $event = Event::fromContexts($open, $complete);
        $this->eventStore->append($event);
    }
}
