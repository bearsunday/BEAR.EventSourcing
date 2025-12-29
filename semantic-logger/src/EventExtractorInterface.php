<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger;

use BEAR\SemanticLogger\Context\CompleteContextInterface;
use BEAR\SemanticLogger\Context\OpenContextInterface;

/**
 * Bridge interface for Event Sourcing integration.
 *
 * This interface is defined in BEAR.SemanticLogger but implemented
 * in BEAR.EventSourcing, following the Dependency Inversion Principle.
 */
interface EventExtractorInterface
{
    /**
     * Extract an event from the completed resource operation.
     *
     * Called in real-time after each successful resource operation.
     * Implementation decides whether to record the event (e.g., skip GET requests).
     */
    public function extract(
        OpenContextInterface $open,
        CompleteContextInterface $complete,
    ): void;
}
