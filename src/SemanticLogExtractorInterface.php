<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

/**
 * @psalm-import-type SemanticLog from Types
 * @phpstan-import-type SemanticLog from Types
 */
interface SemanticLogExtractorInterface
{
    /** @param SemanticLog $semanticLog */
    public function extract(array $semanticLog): Events;
}
