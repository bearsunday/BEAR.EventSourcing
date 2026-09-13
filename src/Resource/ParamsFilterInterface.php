<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

/**
 * Transforms recorded request params before they reach the observation log, and declares
 * whether that transformation still leaves the operation replayable.
 *
 * Removing a key does not automatically mean the request became non-replayable: only the
 * filter knows whether a given key was domain input the handler reads (removing it blocks
 * replay) or transport metadata a replay would mint fresh regardless (removing it does not).
 * See {@see FilteredParams}.
 */
interface ParamsFilterInterface
{
    /** @param array<string, mixed> $params */
    public function __invoke(array $params): FilteredParams;
}
