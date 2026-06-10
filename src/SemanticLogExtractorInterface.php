<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use Koriym\SemanticLogger\LogJson;

/** Converts a flushed Semantic Logger log into extracted events. */
interface SemanticLogExtractorInterface
{
    public function extract(LogJson $semanticLog): EventsInterface;
}
