<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

/**
 * Converts a flushed Semantic Logger log into extracted events.
 *
 * @psalm-import-type SemanticLog from Types
 */
interface SemanticLogExtractorInterface
{
    /** @param SemanticLog $semanticLog */
    public function extract(array $semanticLog): EventsInterface;
}
